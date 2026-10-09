@props([
    'items',
    'type',
    'title',
])

{{--
    Peringkat "paling sering dipinjam" untuk dasbor pustakawan (Task 26.2).

    Dipakai dua kali dengan `type="book"` (buku) dan `type="member"` (anggota).
    Bentuk datanya beda — buku punya `title`, anggota punya `name` — jadi
    pemetaannya dipusatkan di sini lewat `match`, supaya home.blade.php tidak
    perlu merakit array di tengah markup.

    Setiap baris adalah tautan ke daftar yang sudah difilter (`?q=`), bukan ke
    halaman detail. Prinsipnya sama dengan kotak statistik: angka hanya bisa
    dipercaya kalau pembacanya bisa membuka daftar di balik angka itu dan
    melihat barisnya sendiri. Kalau tertaut ke detail, "12 pinjaman" tidak
    bisa diverifikasi dari halaman itu.

    `type` divalidasi supaya typo tidak diam-diam jatuh ke cabang anggota:
    lebih baik error saat render daripada peringkat buku yang menampilkan
    nama orang.
--}}
@php
    $rows = $items->map(function ($item) use ($type) {
        $row = match ($type) {
            'book' => [
                'label' => $item->title,
                'meta' => $item->author?->name,
                'href' => route('books.index', ['q' => $item->title]),
                'value' => (int) $item->loans_count,
            ],
            'member' => [
                'label' => $item->name,
                'meta' => $item->email,
                'href' => route('users.index', ['q' => $item->name]),
                'value' => (int) $item->loans_count,
            ],
            default => throw new \InvalidArgumentException("Tipe peringkat tidak dikenal: {$type}"),
        };

        return $row;
    });

    $max = max(1, (int) $rows->max('value'));
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <h2 class="font-semibold text-primary">{{ $title }}</h2>

    @if ($rows->isEmpty())
        <x-empty-state class="mt-4"
                       :title="__('dashboard.rank_empty')"
                       :description="__('dashboard.rank_empty_description')" />
    @else
        <ol class="mt-4 space-y-4">
            @foreach ($rows as $index => $row)
                <li>
                    <a href="{{ $row['href'] }}"
                       class="group block rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-tertiary">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="min-w-0 truncate font-medium text-primary transition-colors group-hover:text-tertiary">
                                <span class="mr-1.5 font-semibold text-secondary tabular-nums">{{ $index + 1 }}.</span>
                                {{ $row['label'] }}
                            </span>
                            <span class="shrink-0 text-sm font-semibold text-tertiary tabular-nums">
                                {{--
                                    `trans_choice` dikirim KUNCI-nya, bukan hasil `__()`.

                                    `Translator::localeForChoice()` mengecek apakah yang dikirim
                                    itu kunci terdaftar; kalau tidak, ia jatuh ke
                                    `app.fallback_locale` ('id') — dan aturan jamak Bahasa
                                    Indonesia selalu memilih bentuk pertama. Akibatnya versi
                                    Inggris tampil "2 loan" (bukan "2 loans"). Kirim kuncinya
                                    maka locale aktif yang dipakai dan pluralnya benar.
                                --}}
                                {{ trans_choice('dashboard.loan_count', $row['value']) }}
                            </span>
                        </span>

                        <span class="mt-1.5 block h-1.5 w-full overflow-hidden rounded-full bg-secondary/15">
                            <span class="block h-full rounded-full bg-tertiary/70 transition-colors group-hover:bg-tertiary"
                                  style="width: {{ (int) round($row['value'] / $max * 100) }}%"></span>
                        </span>

                        @if ($row['meta'])
                            <span class="mt-1 block truncate text-label text-secondary">{{ $row['meta'] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ol>
    @endif
</div>
