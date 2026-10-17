@extends('layouts.app')

@section('title', $user ? ($isStaff ? __('dashboard.librarian_title') : __('dashboard.member_greeting', ['name' => $user->name])) : config('app.name'))

@section('content')

    @include('partials.home.hero')

    {{-- Statistik umum (untuk tamu & anggota) --}}
    <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="{{ __('home.collection_summary') }}">
        @foreach ([
            ['label' => __('home.available_books'), 'value' => $totalBooks, 'tone' => 'available'],
            ['label' => __('home.currently_borrowed'), 'value' => $currentlyBorrowed, 'tone' => 'borrowed'],
            ['label' => __('home.categories'), 'value' => $totalCategories, 'tone' => 'tertiary'],
            ['label' => __('home.registered_members'), 'value' => $totalMembers, 'tone' => 'primary'],
        ] as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :tone="$stat['tone']" />
        @endforeach
    </section>

    {{--
        Blok dasbor (anggota + pustakawan) diletakkan SEBELUM seluruh bagian
        katalog/buku (permintaan pengguna). Alasannya: ringkasan pribadi
        (sapaan, pinjaman aktif, riwayat) adalah inti beranda anggota dan
        harus langsung terlihat; koleksi buku (Terbaru, Populer, Kategori)
        dikelompokkan rapat di bawahnya sebagai satu kesatuan katalog
        sehingga tidak lagi terpisah jauh dan menyisakan jarak kosong.
    --}}

    @auth
        @if ($isMember)
            @include('partials.home.member-summary', [
                'user' => $user,
                'activeLoanCount' => $activeLoanCount,
                'overdueCount' => $overdueCount,
                'favoriteCount' => $favoriteCount,
                'activeLoans' => $activeLoans,
                'recentLoansMember' => $recentLoansMember,
                'readingHistory' => $readingHistory,
            ])
        @endif

        @if ($isStaff)
            @include('partials.home.staff-dashboard', [
                'totalBooksStaff' => $totalBooksStaff,
                'totalUsers' => $totalUsers,
                'totalLoans' => $totalLoans,
                'totalMembersStaff' => $totalMembersStaff,
                'activeLoansStaff' => $activeLoansStaff,
                'overdueLoansStaff' => $overdueLoansStaff,
                'bookStatus' => $bookStatus,
                'loansPerMonth' => $loansPerMonth,
                'topBorrowedBooks' => $topBorrowedBooks,
                'topBorrowers' => $topBorrowers,
                'recentLoans' => $recentLoans,
                'recentBooks' => $recentBooks,
                'recentActivity' => $recentActivity,
            ])
        @endif
    @endauth

    {{--
        Grafik aktivitas pribadi anggota — diletakkan DI BAWAH blok dasbor
        (permintaan pengguna), tepat setelah sapaan "Halo, …" + pinjaman aktif
        + riwayat, sehingga anggota melihat ringkasan pribadi lalu grafik
        aktivitasnya. Grid memakai `xl:grid-cols-2` (bukan `lg:`) untuk alasan
        yang sama dengan grafik staf: di `lg` 12 batang berebut ruang sampai
        label sumbu X tidak terbaca.

        Guard `$isMember` (bukan sekadar `@auth`): `$myLoansPerMonth` hanya
        diisi oleh cabang anggota di controller, jadi pustakawan yang ikut
        melihat blok ini akan kena error variabel tak terdefinisi.
    --}}
    @if ($isMember)
        @include('partials.home.member-charts', [
            'myLoansPerMonth' => $myLoansPerMonth,
            'myReadingStartsPerMonth' => $myReadingStartsPerMonth,
        ])
    @endif


    {{--
        Koleksi buku (umum) — dikelompokkan rapat setelah blok dasbor
        (permintaan pengguna). Urutannya: Buku Terbaru → Buku Populer →
        Kategori, tanpa jarak kosong yang memisahkan fitur buku dari
        bagian lain.
    --}}
    @include('partials.home.catalog', [
        'latestBooks' => $latestBooks,
        'popularBooks' => $popularBooks,
        'categories' => $categories,
    ])

    {{-- Ajakan bertindak untuk tamu --}}
    @guest
        @if (config('perpustakaan.registration.enabled', true))
            @include('partials.home.join-cta')
        @endif
    @endguest
@endsection
