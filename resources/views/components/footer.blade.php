<footer class="mt-auto border-t border-hairline bg-surface/60">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-md bg-brand text-on-brand" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.25S10.5 4.75 8 4.75c-1.1 0-2 .35-2.75.75v12.5c.75-.4 1.65-.75 2.75-.75 2.5 0 4 1.5 4 1.5s1.5-1.5 4-1.5c1.1 0 2 .35 2.75.75V5.5c-.75-.4-1.65-.75-2.75-.75-2.5 0-4 1.5-4 1.5Zm0 0V18.75" />
                        </svg>
                    </span>
                    <span class="text-sm font-bold tracking-tight text-primary">{{ config('app.name') }}</span>
                </a>

                <p class="mt-4 text-sm text-secondary">
                    Katalog digital untuk menelusuri koleksi, meminjam buku, dan memantau riwayat membaca.
                </p>
            </div>

            <nav aria-label="Tautan Jelajahi">
                <p class="text-label font-semibold tracking-wide text-primary uppercase">Jelajahi</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}"
                            class="text-secondary transition-colors hover:text-tertiary">Beranda</a></li>
                    <li><a href="{{ route('books.index') }}"
                            class="text-secondary transition-colors hover:text-tertiary">Katalog Buku</a></li>
                    <li><a href="{{ route('categories.public') }}"
                            class="text-secondary transition-colors hover:text-tertiary">Kategori</a></li>
                    @guest
                        <li><a href="{{ route('login') }}"
                                class="text-secondary transition-colors hover:text-tertiary">Masuk</a></li>
                    @else
                        <li>
                            <a href="{{ route(auth()->user()->isStaff() ? 'dashboard' : 'anggota.dashboard') }}"
                                class="text-secondary transition-colors hover:text-tertiary">Dasbor Saya</a>
                        </li>
                    @endguest
                </ul>
            </nav>

            <nav aria-label="Tautan Akun">
                <p class="text-label font-semibold tracking-wide text-primary uppercase">Akun</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @guest
                        <li>
                            <a href="{{ route('login') }}"
                                class="text-secondary transition-colors hover:text-tertiary">Masuk</a>
                        </li>
                        @if (config('perpustakaan.registration.enabled', true))
                            <li>
                                <a href="{{ route('register') }}"
                                    class="text-secondary transition-colors hover:text-tertiary">Daftar Anggota</a>
                            </li>
                        @endif
                    @else
                        @if (auth()->user()->isMember())
                            <li>
                                <a href="{{ route('favorites.index') }}"
                                    class="text-secondary transition-colors hover:text-tertiary">Favorit</a>
                            </li>
                            <li>
                                <a href="{{ route('reading-histories.index') }}"
                                    class="text-secondary transition-colors hover:text-tertiary">Riwayat Baca</a>
                            </li>
                        @else
                            <li><span class="text-secondary">Masuk sebagai {{ auth()->user()->roleLabel() }}</span></li>
                        @endif
                    @endguest
                </ul>
            </nav>

            <div>
                <p class="text-label font-semibold tracking-wide text-primary uppercase">Jam Layanan</p>
                <ul class="mt-4 space-y-2.5 text-sm text-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-tertiary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Senin&ndash;Jumat, 08.00&ndash;17.00
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-tertiary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 10.5c0 7.14-7.5 11.25-7.5 11.25S4.5 17.64 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        Kota Malang, Indonesia
                    </li>
                </ul>
            </div>
        </div>

        <div
            class="mt-10 flex flex-col gap-3 border-t border-hairline pt-6 text-sm text-secondary sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}. Proyek demo.</p>
            <p class="text-label">Dibangun dengan Laravel {{ Illuminate\Foundation\Application::VERSION }}</p>
        </div>
    </div>
</footer>
