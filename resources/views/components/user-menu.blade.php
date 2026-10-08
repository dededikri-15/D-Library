@php
    $user = auth()->user();
    $dashboardRoute = $user?->isStaff() ? 'dashboard' : 'anggota.dashboard';

    /*
     * Menu akun (Task 14.2). Isinya sengaja lebih ringkas daripada sidebar:
     * sidebar adalah navigasi utama, dropdown ini jalan pintas ke halaman
     * yang paling sering dibuka plus tombol keluar.
     */
    $menuLinks = array_values(
        array_filter([
            [
                'route' => $dashboardRoute,
                'pattern' => $dashboardRoute,
                'label' => __('navigation.dashboard'),
                'icon' => 'M3.75 3.75h7.5v7.5h-7.5zm9 0h7.5v4.5h-7.5zm0 6h7.5v10.5h-7.5zm-9 3h7.5v7.5h-7.5z',
            ],
            $user?->isStaff()
                ? [
                    'route' => 'books.create',
                    'pattern' => 'books.create',
                    'label' => __('dashboard.add_book'),
                    'icon' => 'M12 4.5v15m7.5-7.5h-15',
                ]
                : null,
            $user?->isStaff()
                ? [
                    'route' => 'loans.index',
                    'pattern' => 'loans.index',
                    'label' => __('dashboard.manage_loans'),
                    'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                ]
                : null,
            $user?->isStaff()
                ? [
                    'route' => 'users.index',
                    'pattern' => 'users.*',
                    'label' => __('dashboard.manage_users'),
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
                    'route' => 'waiting-lists.index',
                    'pattern' => 'waiting-lists.*',
                    'label' => __('navigation.waiting_list'),
                    // Ikon daftar bergaris, bukan jam — jam sudah dipakai
                    // "Riwayat Pinjam" di menu yang sama.
                    'icon' =>
                        'M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
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
        ]),
    );
@endphp

<x-dropdown align="right" menu-label="Menu akun" trigger-class="h-10 px-1.5 sm:px-2.5">
    <x-slot:trigger>
        {{-- `alt=""` karena nama user sudah tertulis di sebelah avatar ini. --}}
        <x-avatar :user="$user" size="xs" alt="" />
        <span class="hidden max-w-32 truncate sm:block">{{ $user->name }}</span>
        <svg data-dropdown-icon class="hidden h-4 w-4 shrink-0 text-secondary transition-transform sm:block" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
        <span class="sr-only">{{ __('navigation.open_account_menu') }}</span>
    </x-slot:trigger>

    <x-slot:menu>
        <div role="presentation" class="flex items-center gap-3 px-3 pt-2 pb-3">
            <x-avatar :user="$user" size="md" alt="" />
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-primary">{{ $user->name }}</span>
                <span class="mt-0.5 block truncate text-label text-secondary">{{ $user->email }}</span>
                <span class="badge badge-muted mt-2">{{ $user->roleLabel() }}</span>
            </span>
        </div>

        <div role="separator" class="dropdown-separator"></div>
        <p role="presentation" class="dropdown-label">{{ __('navigation.menu') }}</p>

        {{--
            Profil melingkupi semua item lain: available untuk anggota maupun
            pustakawan, jadi posisinya di paling atas daftar dan bukan di bawah
            blok yang hanya untuk staff.
        --}}
        <a href="{{ route('profile.show') }}" role="menuitem"
            @class(['dropdown-item', 'dropdown-item-active' => request()->routeIs('profile.*')])>
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 1 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            <span class="truncate">{{ __('navigation.profile') }}</span>
        </a>

        @foreach ($menuLinks as $link)
            <a href="{{ route($link['route']) }}" role="menuitem"
                @class([
                    'dropdown-item',
                    'dropdown-item-active' => request()->routeIs($link['pattern']),
                ])>
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                </svg>
                <span class="truncate">{{ $link['label'] }}</span>
            </a>
        @endforeach

        <div role="separator" class="dropdown-separator"></div>

        <form method="POST" action="{{ route('logout') }}" role="none">
            @csrf
            <button type="submit" role="menuitem" class="dropdown-item">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M18 15l3-3m0 0-3-3m3 3H9" />
                </svg>
                <span>{{ __('navigation.logout') }}</span>
            </button>
        </form>
    </x-slot:menu>
</x-dropdown>
