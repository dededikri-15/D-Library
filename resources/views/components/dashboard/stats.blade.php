{{--
    Statistik yang dipakai bersama dashboard staff.
    Dipisah jadi komponen supaya angka dan urutannya tidak pernah berbeda
    di antara dasbor meski keduanya menampilkan data yang sama. Slot berisi
    tombol aksi, sehingga tiap dasbor bisa menampilkan tautan yang relevan
    dengan role-nya.

    Setiap kotak adalah satu tautan ke daftar yang memang sudah ada, dengan
    filter yang sudah ada di halaman tujuan. Alasannya: angka tanpa daftar yang bisa dibuka
    hanya bisa dipercaya atau tidak — orang tidak bisa memverifikasi sendiri
    bahwa "Total Buku 21" itu memang 21 buku, dan kalau ada yang salah input
    tidak ada jalan untuk melihat mana yang salah.

    Petunjuk "Lihat daftar" di dalam kotak bukan hiasan. Kotak yang bisa diklik
    tapi terlihat seperti angka biasa sering tidak diklik sama sekali, karena
    tidak ada yang memberi tahu kotak itu sebuah tautan.
--}}

@props([
    'totalBooks',
    'totalUsers',
    'totalMembers',
    'totalLoans',
    'activeLoans',
    'overdueLoans',
    'bookStatus' => [],
])

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
    @php
        // Tautan per kotak. Dipisah dari array di bawah supaya daftar isi
        // kartu (label, nilai, ikon) tetap mudah dibaca sebagai satu blok,
        // sementara URL-nya — yang panjang dan kadang bawa query string —
        // tidak menutupi daftar itu.
        //
        // Setiap URL memakai filter yang sudah didukung halaman tujuannya,
        // jadi angkanya di sini dan isi daftarnya MUSTAHIL berbeda:
        //   - books.index  : staff melihat semua buku, termasuk nonaktif
        //   - users.index  : ?role=anggota untuk kotak "Anggota"
        //   - loans.index  : ?active=1 = borrowed + overdue, sama dengan
        //                    scopeActive() yang dipakai kartu "Peminjaman Aktif"
        $statLinks = [
            'Total Buku' => route('books.index'),
            'Pengguna' => route('users.index'),
            'Anggota' => route('users.index', ['role' => \App\Models\User::ROLE_ANGGOTA]),
            'Total Peminjaman' => route('loans.index'),
            'Peminjaman Aktif' => route('loans.index', ['active' => 1]),
        ];
    @endphp

    @foreach ([
        ['label' => 'Total Buku', 'value' => $totalBooks, 'icon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
        ['label' => 'Pengguna', 'value' => $totalUsers, 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['label' => 'Anggota', 'value' => $totalMembers, 'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
        ['label' => 'Total Peminjaman', 'value' => $totalLoans, 'icon' => 'M8 7.5h8m-8 4h8m-8 4h5m-8.5 5h15A2.5 2.5 0 0 0 22 18V6a2.5 2.5 0 0 0-2.5-2.5h-15A2.5 2.5 0 0 0 2 6v12a2.5 2.5 0 0 0 2.5 2.5Z'],
        ['label' => 'Peminjaman Aktif', 'value' => $activeLoans, 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ] as $stat)
        {{--
            Kotak stat-nya sendiri yang jadi <a>, bukan cuma tombol kecil di
            dalamnya: area klik sebesar itulah yang dipakai jari di layar sentuh,
            dan target sentuh yang kecil sulit dikenali.

            `focus-visible` (+ bukan `focus`) supaya cincin fokus hanya muncul
            untuk navigasi keyboard — dengan `focus`, kotak ini akan bergaris
            setiap kali diklik mouse, dan di halaman yang isinya semua bisa
            diklik, garis fokus itu berantakan.
        --}}
        <a href="{{ $statLinks[$stat['label']] }}"
           class="stat-card block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-tertiary">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-label font-medium tracking-wide text-secondary uppercase">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-primary">
                        {{ number_format($stat['value'], 0, ',', '.') }}
                    </p>
                </div>

                <span class="stat-icon shrink-0 bg-tertiary/10 text-tertiary dark:bg-tertiary/15" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" />
                    </svg>
                </span>
            </div>

            <p class="mt-4 flex items-center gap-1 text-label font-medium text-tertiary">
                Lihat daftar
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </p>
        </a>
    @endforeach
</div>

<div class="mt-6 grid gap-4 lg:grid-cols-2">
    <div class="card p-5">
        <h2 class="font-semibold text-primary">Status Buku</h2>
        <dl class="mt-4 space-y-3 text-sm">
            @foreach ([['Tersedia', App\Models\Book::STATUS_AVAILABLE, 'bg-available'], ['Dipinjam', App\Models\Book::STATUS_BORROWED, 'bg-borrowed'], ['Tidak aktif', App\Models\Book::STATUS_INACTIVE, 'bg-secondary']] as [$label, $key, $dot])
                <div class="flex items-center justify-between">
                    <dt class="flex items-center gap-2 text-secondary">
                        <span class="inline-block h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                        {{ $label }}
                    </dt>
                    <dd class="font-medium tabular-nums text-primary">{{ $bookStatus[$key] ?? 0 }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="card p-5">
        <h2 class="font-semibold text-primary">Peminjaman</h2>
        <div class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between rounded-lg bg-borrowed/10 px-3 py-2.5 dark:bg-borrowed/15">
                <span class="text-borrowed">Sedang dipinjam</span>
                <span class="font-semibold tabular-nums text-borrowed">{{ $activeLoans }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-overdue/10 px-3 py-2.5 dark:bg-overdue/15">
                <span class="text-overdue">Terlambat</span>
                <span class="font-semibold tabular-nums text-overdue">{{ $overdueLoans }}</span>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-3 border-t border-hairline pt-4">
            {{ $slot }}
        </div>
    </div>
</div>
