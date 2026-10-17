@php
    $user = auth()->user();
    $isStaff = (bool) $user?->isStaff();

    /*
     * Jumlah pengajuan perpanjangan kartu yang masih menunggu konfirmasi.
     * Dipakai sebagai lencana di menu "Pengajuan Kartu" supaya pustakawan
     * langsung tahu ada anggota menunggu tanpa membuka halamannya dulu.
     * Hanya dihitung untuk staf — anggota tidak perlu tahu angka ini.
     */
    $pendingCardRequests = $isStaff
        ? App\Models\LibraryCard::whereNotNull('renewal_requested_at')->count()
        : 0;

    /*
     * Sorotan menu buku (Katalog vs Kelola Buku).
     *
     * Dulu keduanya memakai pola `books.*` yang sama, padahal URL-nya juga
     * sama persis (/buku) — akibatnya keduanya menyala bersamaan, entah dari
     * menu mana pengguna masuk. Sekarang URL-nya dipisah: staf membuka
     * katalog lewat `/buku?lihat=katalog` dan kelola lewat `/buku`, jadi
     * cukup dihitung dari mode halaman yang sedang dibuka. Halaman detail,
     * baca, create, dan edit ikut dihitung supaya tidak ada halaman buku
     * yang menyala dua menu sekaligus.
     */
    $onBooksIndex = request()->routeIs('books.index');
    $onCatalogBooks = request()->routeIs(['books.show', 'books.read'])
        || ($onBooksIndex && (! $isStaff || request()->query('lihat') === 'katalog'));
    $onManageBooks = $isStaff && (
        ($onBooksIndex && request()->query('lihat') !== 'katalog')
        || request()->routeIs(['books.create', 'books.edit'])
    );

    $links = array_values(
        array_filter([
            [
                'route' => 'home',
                'pattern' => 'home',
                'label' => __('navigation.home'),
                'icon' => 'M3 10.5 12 3l9 7.5M5.25 9v11.25h13.5V9M9 20.25v-7.5h6v7.5',
            ],
            [
                'route' => 'books.index',
                'params' => $isStaff ? ['lihat' => 'katalog'] : [],
                'active' => $onCatalogBooks,
                'label' => __('navigation.catalog'),
                'icon' =>
                    'M12 6.25S10.5 4.75 8 4.75c-1.1 0-2 .35-2.75.75v12.5c.75-.4 1.65-.75 2.75-.75 2.5 0 4 1.5 4 1.5s1.5-1.5 4-1.5c1.1 0 2 .35 2.75.75V5.5c-.75-.4-1.65-.75-2.75-.75-2.5 0-4 1.5-4 1.5Zm0 0V18.75',
            ],
            [
                'route' => 'categories.public',
                'pattern' => 'categories.public',
                'label' => __('navigation.categories'),
                'icon' =>
                    'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z',
            ],

            $user?->isStaff()
                ? [
                    'route' => 'books.index',
                    'active' => $onManageBooks,
                    'label' => __('navigation.manage_books'),
                    'icon' =>
                        'M4.5 5.25A2.25 2.25 0 0 1 6.75 3h12A2.25 2.25 0 0 1 21 5.25v13.5A2.25 2.25 0 0 1 18.75 21h-12A2.25 2.25 0 0 1 4.5 18.75zM8 7.5h9m-9 4.5h9m-9 4.5h5',
                ]
                : null,
            /*
                Grup "Kelola Data".

                Kenapa perlu: halaman /kategori, /penulis, dan /penerbit
                sebelumnya tidak punya tautan dari menu mana pun — satu-satunya
                cara menuju sana adalah mengetik URL-nya sendiri, padahal di
                situ justru letak tombol hapus. Staff bisa menambah kategori
                lewat form buku, tapi tidak bisa mengoreksinya lagi.

                `pattern` ditulis sebagai daftar, bukan `categories.*`. Kalau
                memakai `categories.*`, item ini ikut aktif di halaman publik
                `categories.public` — sehingga dua menu menyala bersamaan.
            */
            $user?->isStaff()
                ? [
                    'heading' => __('navigation.manage_data'),
                    'route' => 'categories.index',
                    'pattern' => ['categories.index', 'categories.create', 'categories.edit'],
                    'label' => __('navigation.categories'),
                    'icon' =>
                        'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
                ]
                : null,
            $user?->isStaff()
                ? [
                    'route' => 'authors.index',
                    'pattern' => ['authors.index', 'authors.create', 'authors.edit'],
                    'label' => __('navigation.authors'),
                    'icon' =>
                        'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
                ]
                : null,
            $user?->isStaff()
                ? [
                    'route' => 'publishers.index',
                    'pattern' => ['publishers.index', 'publishers.create', 'publishers.edit'],
                    'label' => __('navigation.publishers'),
                    'icon' =>
                        'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                ]
                : null,
            $user?->isStaff()
                ? [
                    'route' => 'loans.index',
                    'pattern' => 'loans.*',
                    'label' => __('navigation.loans'),
                    'icon' => 'M12 6v6l4 2m4-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                ]
                : null,
            /*
                Pengajuan perpanjangan kartu anggota — daftar khusus staf
                (route `library-cards.requests`). Lencana angka muncul hanya
                kalau ada pengajuan yang menunggu, supaya menu ini berfungsi
                sekaligus sebagai pengingat.
            */
            $user?->isStaff()
                ? [
                    'route' => 'library-cards.requests',
                    'pattern' => 'library-cards.requests',
                    'label' => __('navigation.card_requests'),
                    'badge' => $pendingCardRequests > 0 ? $pendingCardRequests : null,
                    'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
                ]
                : null,
            $user?->isStaff()
                ? [
                    'route' => 'users.index',
                    'pattern' => 'users.*',
                    'label' => __('navigation.users'),
                    'icon' =>
                        'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z',
                ]
                : null,
            $user?->isMember()
                ? [
                    'route' => 'loans.mine',
                    'pattern' => 'loans.mine',
                    'label' => __('navigation.loan_history'),
                    'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                ]
                : null,
            $user?->isMember()
                ? [
                    'route' => 'favorites.index',
                    'pattern' => 'favorites.*',
                    'label' => __('navigation.favorites'),
                    'icon' =>
                        'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z',
                ]
                : null,
            $user?->isMember()
                ? [
                    'route' => 'reading-histories.index',
                    'pattern' => 'reading-histories.*',
                    'label' => __('navigation.reading_history'),
                    'icon' => 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                ]
                : null,
            $user?->isMember()
                ? [
                    'route' => 'library-cards.show',
                    'pattern' => 'library-cards.*',
                    'label' => __('navigation.library_card'),
                    'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
                ]
                : null,
        ]),
    );
@endphp

<header>
    @include('components.navbar.sidebar', ['links' => $links])

    @include('components.navbar.topbar')
</header>
