@php
    $user = auth()->user();
    $dashboardRoute = $user?->isStaff() ? 'dashboard' : 'anggota.dashboard';

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
                'pattern' => 'books.*',
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
            $user
                ? [
                    'route' => $dashboardRoute,
                    'pattern' => $dashboardRoute,
                    'label' => __('navigation.dashboard'),
                    'icon' => 'M3.75 3.75h7.5v7.5h-7.5zm9 0h7.5v4.5h-7.5zm0 6h7.5v10.5h-7.5zm-9 3h7.5v7.5h-7.5z',
                ]
                : null,
            $user?->isStaff()
                ? [
                    'route' => 'books.index',
                    'pattern' => 'books.*',
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
                    'route' => 'mailbox.index',
                    'pattern' => 'mailbox.*',
                    'label' => __('navigation.mailbox'),
                    'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 5.625a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
                ]
                : null,
        ]),
    );
@endphp

<header>
    <aside id="app-sidebar" data-sidebar aria-label="{{ __('navigation.primary_navigation') }}"
        class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-hairline bg-surface px-3 py-4 shadow-glass transition-[width,transform] duration-200 lg:translate-x-0">
        <a href="{{ route('home') }}" title="D-Library" class="group flex min-h-12 items-center gap-3 rounded-md px-2.5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-brand text-on-brand shadow-lift"
                aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.25S10.5 4.75 8 4.75c-1.1 0-2 .35-2.75.75v12.5c.75-.4 1.65-.75 2.75-.75 2.5 0 4 1.5 4 1.5s1.5-1.5 4-1.5c1.1 0 2 .35 2.75.75V5.5c-.75-.4-1.65-.75-2.75-.75-2.5 0-4 1.5-4 1.5Zm0 0V18.75" />
                </svg>
            </span>
            <span data-sidebar-label class="min-w-0 leading-tight">
                <span class="block truncate text-sm font-bold text-primary">D-Library</span>
                <span class="mt-1 block text-[0.625rem] font-medium tracking-widest text-secondary uppercase">Digital
                    Library</span>
            </span>
        </a>

        {{--
            Tombol tutup drawer (Task 14.9). Hanya muncul di bawah `lg`, karena
            di desktop sidebar tidak pernah menjadi drawer.

            Alasannya: burger menu ada di topbar yang z-30, sedangkan drawer ini
            z-50 dan menutupi bagian kiri topbar — termasuk burger itu. Jadi
            ketika drawer terbuka di HP, satu-satunya cara menutupnya tinggal
            klik area gelap di sebelahnya, dan cara itu tidak bisa ditemukan
            pengguna. Escape juga tidak ada di layar sentuh, jadi drawer harus
            punya tombol tutup sendiri.

            `data-sidebar-close` dibaca `initSidebar()`; tombol ini TIDAK memakai
            `data-sidebar-toggle` supaya tidak ada dua kontrol dengan
            `aria-expanded` yang valuanya bisa berbeda.
        --}}
        <button type="button" data-sidebar-close
            class="absolute top-4 right-2 grid h-10 w-10 place-items-center rounded-md text-secondary
                transition-colors hover:bg-secondary/10 hover:text-primary lg:hidden"
            aria-label="{{ __('navigation.close_navigation') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <nav class="mt-8 flex-1 space-y-1 overflow-y-auto" aria-label="{{ __('navigation.menu') }}">
            <p data-sidebar-label class="mb-2 px-3 text-label font-semibold tracking-widest text-secondary uppercase">
                {{ __('navigation.menu') }}</p>
            @foreach ($links as $link)
                {{--
                    Judul kelompok (mis. "Kelola Data") hanya ditulis di item
                    pertama grupnya. Spasi antarkelompok pakai `pt-*`, bukan
                    `mt-*`, karena `nav` sudah pakai `space-y-1` yang memasang
                    margin-top pada setiap anak — dua utility margin yang
                    bertabrakan itu urutannya di CSS tidak bisa ditebak.
                --}}
                @if (! empty($link['heading']))
                    <p data-sidebar-label
                       class="px-3 pt-5 pb-1 text-label font-semibold tracking-widest text-secondary uppercase">
                        {{ $link['heading'] }}
                    </p>
                @endif
                <a href="{{ route($link['route']) }}" title="{{ $link['label'] }}" @class([
                    'sidebar-link',
                    'sidebar-link-active' => request()->routeIs($link['pattern']),
                ])>
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                    </svg>
                    <span data-sidebar-label class="truncate">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-hairline pt-3">
            @auth
                {{--
                    Blok identitas di kaki sidebar sekaligus jalan pintas ke
                    halaman profil. `title` menjaga keterangan tetap ada ketika
                    sidebar dalam keadaan ciut (label disembunyikan), sama seperti
                    item menu lain di atas.
                --}}
                <a href="{{ route('profile.show') }}" title="{{ __('navigation.profile') }}"
                   class="flex items-center gap-3 rounded-md px-2.5 py-2 transition-colors hover:bg-secondary/5">
                    {{-- Avatar: foto kalau ada, ilustrasi sesuai jenis kelamin kalau tidak. --}}
                    <x-avatar :user="$user" size="sm" alt="" />
                    <span data-sidebar-label class="min-w-0 flex-1 leading-tight">
                        <span class="block truncate text-sm font-medium text-primary">{{ $user->name }}</span>
                        <span class="mt-1 block text-label text-secondary">{{ $user->roleLabel() }}</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="{{ __('navigation.logout') }}" class="sidebar-link w-full">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M18 15l3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <span data-sidebar-label>{{ __('navigation.logout') }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="sidebar-link">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M18 15l3-3m0 0-3-3m3 3H9" />
                    </svg>
                    <span data-sidebar-label>{{ __('navigation.login') }}</span>
                </a>
                @if (config('perpustakaan.registration.enabled', true))
                    <a href="{{ route('register') }}" class="sidebar-link">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span data-sidebar-label>{{ __('navigation.register') }}</span>
                    </a>
                @endif
            @endauth
        </div>
    </aside>

    <div data-sidebar-backdrop hidden class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"></div>

    <div
        class="app-topbar sticky top-0 z-30 flex min-h-16 flex-wrap items-center gap-3 border-b border-hairline bg-neutral/90 px-4 py-2 backdrop-blur-xl transition-[margin] duration-200 max-[360px]:gap-2 max-[360px]:px-3 sm:px-6 lg:ml-64">
        <button type="button" data-sidebar-toggle aria-expanded="false" aria-controls="app-sidebar"
            aria-label="{{ __('navigation.open_sidebar') }}" title="{{ __('navigation.open_sidebar') }}" class="btn btn-ghost h-10 w-10 shrink-0 p-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm font-bold text-primary lg:hidden">
            <span class="grid h-8 w-8 place-items-center rounded-md bg-brand text-on-brand" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.25S10.5 4.75 8 4.75c-1.1 0-2 .35-2.75.75v12.5c.75-.4 1.65-.75 2.75-.75 2.5 0 4 1.5 4 1.5s1.5-1.5 4-1.5c1.1 0 2 .35 2.75.75V5.5c-.75-.4-1.65-.75-2.75-.75-2.5 0-4 1.5-4 1.5Zm0 0V18.75" />
                </svg>
            </span>
            <span class="max-[360px]:sr-only">D-Library</span>
        </a>

        <x-public-stats />

        <div class="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-2 lg:ml-0">
            <x-theme-toggle />
            @auth
                <x-user-menu />
            @else
                <a href="{{ route('login') }}" class="btn btn-blue-outline btn-sm lg:hidden">{{ __('navigation.login') }}</a>
                @if (config('perpustakaan.registration.enabled', true))
                    <a href="{{ route('register') }}" class="btn btn-blue-primary btn-sm lg:hidden">{{ __('navigation.register') }}</a>
                @endif
            @endauth
        </div>
    </div>
</header>
