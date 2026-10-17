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
                {{-- Notifikasi dipisah dari menu akun: daftar tindakan (menu)
                     dan daftar kabar (lonceng) punya isi yang berbeda. --}}
                <x-notification-bell />
                <x-user-menu />
            @else
                <a href="{{ route('login') }}" class="btn btn-blue-outline btn-sm lg:hidden">{{ __('navigation.login') }}</a>
                @if (config('perpustakaan.registration.enabled', true))
                    <a href="{{ route('register') }}" class="btn btn-blue-primary btn-sm lg:hidden">{{ __('navigation.register') }}</a>
                @endif
            @endauth
        </div>
    </div>
