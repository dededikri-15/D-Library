    {{--
        Sidebar sengaja `z-50` hanya di MOBILE: di sana ia berfungsi sebagai
        drawer yang harus menutupi topbar dan konten. Di desktop (`lg:`) ia
        diturunkan ke `z-20` SUPAYA KALAH dari topbar (`z-30`).

        Alasannya bug lonceng notifikasi: panel dropdown ada di dalam topbar,
        jadi walaupun panelnya `z-50`, stacking context-nya tetap topbar.
        Dengan sidebar `z-50`, panel yang melebar ke kiri melewati batas
        `ml-64` (tempat lonceng berada) ditindih sidebar dan yang terlihat
        hanya potongan kanannya — persis laporan "notifikasi pas dibuka
        setengah/ga keliatan".
    --}}
    <aside id="app-sidebar" data-sidebar aria-label="{{ __('navigation.primary_navigation') }}"
        class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-hairline bg-surface px-3 py-4 shadow-glass transition-[width,transform] duration-200 lg:translate-x-0 lg:z-20">
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
                <a href="{{ route($link['route'], $link['params'] ?? []) }}" title="{{ $link['label'] }}" @class([
                    'sidebar-link',
                    'sidebar-link-active' => $link['active'] ?? request()->routeIs($link['pattern']),
                ])>
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                    </svg>
                    <span data-sidebar-label class="truncate">{{ $link['label'] }}</span>
                    @if (! empty($link['badge']))
                        <span data-sidebar-label
                              class="ml-auto inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-tertiary/15 px-1.5 text-[0.65rem] font-semibold text-tertiary">
                            {{ $link['badge'] }}
                        </span>
                    @endif
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

                <div data-sidebar-label class="rounded-md bg-secondary/5 px-2.5 py-2 text-label text-secondary">
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ __('navigation.last_login') }}</span>
                        <span class="font-medium text-primary">{{ $user->lastLoginAt()?->format('d M Y H:i') ?? '-' }}</span>
                    </div>
                </div>
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
