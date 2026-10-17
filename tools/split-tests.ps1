# Alat bantu internal refaktor pengujian - memecah satu file tes menjadi
# beberapa kelas dengan potongan rentang baris, sambil mempertahankan
# namespace, import yang relevan, dan mekanisme penemuan tes PHPUnit.
# Hapus file ini setelah refaktor selesai.
function Split-Test {
    param(
        [Parameter(Mandatory)] [string]$Source,       # mis. 'Feature\DigitalReadingTest.php'
        [Parameter(Mandatory)] [int]$KeepStart,       # fungsi pertama yang TETAP di kelas asli
        [Parameter(Mandatory)] [hashtable[]]$Groups   # @{ Name='X'; Start=91 }
    )

    $path = Join-Path 'tests' $Source
    $resolved = (Resolve-Path $path).Path
    $lines = [System.IO.File]::ReadAllLines($resolved)
    $total = $lines.Length

    $classIdx = -1
    for ($i = 0; $i -lt $total; $i++) { if ($lines[$i] -match '^class ') { $classIdx = $i; break } }
    if ($classIdx -lt 0) { throw "class tidak ditemukan: $Source" }

    $hdrEnd = -1
    for ($i = $classIdx; $i -lt [Math]::Min($classIdx + 8, $total); $i++) {
        if ($lines[$i] -match '^\s+use RefreshDatabase;') { $hdrEnd = $i; break }
    }
    if ($hdrEnd -lt 0) { throw "RefreshDatabase tidak ditemukan: $Source" }

    $namespace = ''
    foreach ($l in $lines) { if ($l -match '^namespace\s+([\w\\]+);') { $namespace = $Matches[1]; break } }
    $origUses = @($lines | Where-Object { $_ -match '^use ' })

    # Baris penutup kelas asli ('}' di kolom 0 terakhir)
    $classEnd = -1
    for ($i = $total - 1; $i -ge 0; $i--) { if ($lines[$i] -eq '}') { $classEnd = $i; break } }

    # Input `Start`/`KeepStart` adalah nomor baris 1-based (dari Select-String);
    # konversi ke indeks array 0-based, lalu naik melewati atribut/komentar/blank.
    $realStart = {
        param([int]$lineNo)
        $s = $lineNo - 1
        while ($s -gt 0) {
            $t = $lines[$s - 1].Trim()
            if ($t -eq '' -or $t.StartsWith('/*') -or $t.StartsWith('*') -or $t.StartsWith('//') -or $t.StartsWith('#') -or $t.StartsWith('|')) { $s-- } else { break }
        }
        while ($s -lt ($lineNo - 1) -and $lines[$s].Trim() -eq '') { $s++ }
        return $s
    }

    # Semua rentang: original + groups
    $starts = @(@{ Name = $null; Start = (& $realStart $KeepStart) })
    foreach ($g in $Groups) { $starts += @{ Name = $g.Name; Start = (& $realStart $g.Start) } }
    $starts = @($starts | Sort-Object { $_.Start })

    # Validasi: nama unik & file belum ada
    foreach ($g in $Groups) {
        if (-not (Test-Path (Join-Path (Split-Path $path -Parent) "$($g.Name).php"))) { }
        else { throw "File sudah ada: $($g.Name).php" }
    }

    $allDefined = @()
    foreach ($s in $starts) {
        $e = if ($s -eq $starts[-1]) { $classEnd - 1 } else { ($starts[$starts.IndexOf($s) + 1].Start - 1) }
        foreach ($m in [regex]::Matches(($lines[$s.Start..$e] -join "`n"), '(?m)^\s+(?:private|protected|public)(?:\s+static)?\s+function\s+(\w+)')) { $allDefined += $m.Groups[1].Value }
    }

    # Trait lintas-keluarga: method yang didefinisikan di tests/Concerns -
    # potongan mana pun yang memakainya otomatis mendapat `use` trait-nya.
    $traitMap = @{
        'member' = 'Tests\Concerns\CreatesLibraryUsers'
        'librarian' = 'Tests\Concerns\CreatesLibraryUsers'
        'staff' = 'Tests\Concerns\CreatesLibraryUsers'
        'borrowedBook' = 'Tests\Concerns\ProvidesBorrowedBook'
        'assertActionLogged' = 'Tests\Concerns\AssertsActionLogged'
        'assertNoActionLogged' = 'Tests\Concerns\AssertsActionLogged'
        
        'anggota' = 'Tests\Concerns\ProvidesLibraryCardUsers'
        'pustakawan' = 'Tests\Concerns\ProvidesLibraryCardUsers'
        'profilePayload' = 'Tests\Concerns\ProvidesProfilePayload'
        'loanFor' = 'Tests\Concerns\ProvidesLoanFixture'
        'bookPayload' = 'Tests\Concerns\ProvidesBookUploadFixtures'
        'bookWithPdf' = 'Tests\Concerns\ProvidesBookUploadFixtures'
    }
    $traitShort = @{
        'Tests\Concerns\CreatesLibraryUsers' = 'CreatesLibraryUsers'
        'Tests\Concerns\ProvidesBorrowedBook' = 'ProvidesBorrowedBook'
        'Tests\Concerns\AssertsActionLogged' = 'AssertsActionLogged'
        'Tests\Concerns\ProvidesSecurityBookPayload' = 'ProvidesSecurityBookPayload'
        'Tests\Concerns\ProvidesLibraryCardUsers' = 'ProvidesLibraryCardUsers'
        'Tests\Concerns\ProvidesProfilePayload' = 'ProvidesProfilePayload'
        'Tests\Concerns\ProvidesLoanFixture' = 'ProvidesLoanFixture'
        'Tests\Concerns\ProvidesBookUploadFixtures' = 'ProvidesBookUploadFixtures'
    }

    $neededTraits = {
        param([string]$body, [string[]]$definedHere)
        $need = @()
        foreach ($c in [regex]::Matches($body, '\$this->(\w+)\(') | ForEach-Object { $_.Groups[1].Value } | Sort-Object -Unique) {
            if ($definedHere -notcontains $c -and $traitMap.ContainsKey($c)) { $need += $traitMap[$c] }
        }
        return @($need | Sort-Object -Unique)
    }

    $enc = New-Object System.Text.UTF8Encoding($false)
    $dir = Split-Path $path -Parent
    $report = @()

    foreach ($s in $starts) {
        $e = if ($s -eq $starts[-1]) { $classEnd - 1 } else { ($starts[$starts.IndexOf($s) + 1].Start - 1) }
        $slice = @($lines[$s.Start..$e])
        while ($slice.Count -gt 0 -and $slice[-1].Trim() -eq '') { $slice = $slice[0..($slice.Count - 2)] }
        while ($slice.Count -gt 0 -and $slice[0].Trim() -eq '') { $slice = $slice[1..($slice.Count - 1)] }
        $body = $slice -join "`n"

        # Helper yang dipakai tapi didefinisikan di potongan lain
        $definedHere = @([regex]::Matches($body, '(?m)^\s+(?:private|protected|public)(?:\s+static)?\s+function\s+(\w+)') | ForEach-Object { $_.Groups[1].Value })
        $needTraits = @(& $neededTraits $body $definedHere)

        # Peringatan untuk helper lintas-potongan yang BUKAN berasal dari trait
        foreach ($c in [regex]::Matches($body, '\$this->(\w+)\(') | ForEach-Object { $_.Groups[1].Value } | Sort-Object -Unique) {
            if ($allDefined -contains $c -and $definedHere -notcontains $c -and -not $traitMap.ContainsKey($c)) {
                $report += "PERINGATAN: potongan '$($s.Name)' memakai helper '$c' yang ada di potongan lain - pindahkan ke tests/Concerns atau gabungkan potongannya"
            }
        }

        if ($null -eq $s.Name) {
            # Kelas asli: header + baris trait yang dibutuhkan (bila belum ada)
            $headerText = ($lines[0..$hdrEnd] -join "`n")
            $out = @()
            for ($k = 0; $k -le $hdrEnd; $k++) {
                $out += $lines[$k]
                if ($lines[$k] -match '^\s+use RefreshDatabase;') {
                    foreach ($t in $needTraits) {
                        $shortT = $traitShort[$t]
                        if ($headerText -notmatch [regex]::Escape($shortT)) { $out += "    use $shortT;" }
                    }
                }
            }
            $out += @('') + $slice + @('}')
            $newPath = $resolved
        } else {
            $uses = @()
            foreach ($u in $origUses) {
                $short = ($u.TrimEnd(';') -replace '^use\s+', '' -split '\\')[-1]
                if ($body -match "(?<![\w\\])$([regex]::Escape($short))(?![\w])") { $uses += $u.Trim() }
            }
            if (-not ($uses -match 'Tests\\TestCase')) { $uses += 'use Tests\TestCase;' }
            if (-not ($uses -match 'RefreshDatabase')) { $uses += 'use Illuminate\Foundation\Testing\RefreshDatabase;' }
            foreach ($t in $needTraits) { $uses += "use $t;" }
            $classLine = if ($lines[$classIdx] -match 'class\s+\w+') { ($lines[$classIdx] -replace 'class\s+\w+', "class $($s.Name)") } else { "class $($s.Name)" }
            $traitLines = @($needTraits | ForEach-Object { "    use $($traitShort[$_]);" })
            $out = @('<?php', '', "namespace $namespace;", '') + $uses + @('', $classLine, '{', '    use RefreshDatabase;') + $traitLines + @('') + $slice + @('}')
            $newPath = Join-Path $dir "$($s.Name).php"
        }

        $content = ($out -join "`n") + "`n"
        [System.IO.File]::WriteAllText($newPath, $content, $enc)
        $lineCount = ($content -split "`n").Count
        $status = if ($lineCount -gt 100) { 'LEBIH-DARI-100!' } else { 'ok' }
        $report += ("{0,-40} {1,4} baris  {2}" -f $(if ($null -eq $s.Name) { "(original) $Source" } else { "$($s.Name).php" }), $lineCount, $status)
    }

    # Cek tutupan: semua baris 0..classEnd-1 harus terbagi habis
    Write-Output ($report -join "`n")
    Write-Output "SUMBER: $Source ($total baris) -> $($Groups.Count + 1) file"
}
