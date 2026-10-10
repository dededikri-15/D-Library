<footer class="relative mt-auto border-t border-hairline bg-surface/60">
    {{-- Garis tipis bergradasi di tepi atas: memisahkan konten dari footer tanpa
         menambah blok warna tebal. Warna pakai token --color-tertiary, jadi ikut
         tema terang/gelap. --}}
    <span class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-tertiary/50 to-transparent"
        aria-hidden="true"></span>

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="group inline-flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand text-on-brand shadow-card transition-transform duration-300 group-hover:-translate-y-0.5 motion-reduce:transform-none" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.25S10.5 4.75 8 4.75c-1.1 0-2 .35-2.75.75v12.5c.75-.4 1.65-.75 2.75-.75 2.5 0 4 1.5 4 1.5s1.5-1.5 4-1.5c1.1 0 2 .35 2.75.75V5.5c-.75-.4-1.65-.75-2.75-.75-2.5 0-4 1.5-4 1.5Zm0 0V18.75" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-bold tracking-tight text-primary">{{ config('app.name') }}</span>
                        <span class="block text-label font-semibold tracking-widest text-tertiary uppercase">{{ __('footer.tagline') }}</span>
                    </span>
                </a>

                <p class="mt-4 max-w-xs text-sm leading-relaxed text-secondary">
                    {{ __('footer.description') }}
                </p>

                {{--
                    Baris ini semula "Kota Malang, Indonesia" (dipindah dari kolom
                    "Jam Layanan"). Pengguna minta diganti: alamat fisik kurang
                    relevan untuk perpustakaan digital, jadi yang ditampilkan
                    sekarang pilihan bahasa — fitur yang memang ada di aplikasi
                    (indonesia/inggris). Ikon globe digambar dari lingkaran +
                    garis khatulistiwa + elips tegak supaya bentuknya pasti benar.
                --}}
                <p class="mt-3 flex items-center gap-2 text-sm text-secondary">
                    <svg class="h-4 w-4 shrink-0 text-tertiary" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3c2.4 2.6 3.6 5.6 3.6 9S14.4 18.4 12 21c-2.4-2.6-3.6-5.6-3.6-9S9.6 5.6 12 3Z" />
                    </svg>
                    {{ __('footer.language_note') }}
                </p>
            </div>

            <nav aria-label="{{ __('footer.explore') }}">
                <span class="block h-1 w-8 rounded-full bg-tertiary/60" aria-hidden="true"></span>
                <p class="mt-3 text-label font-semibold tracking-wide text-primary uppercase">{{ __('footer.explore') }}</p>
                <ul class="mt-4 space-y-1 text-sm">
                    <li><a href="{{ route('home') }}"
                            class="footer-link">{{ __('navigation.home') }}</a></li>
                    <li><a href="{{ route('books.index') }}"
                            class="footer-link">{{ __('footer.catalog') }}</a></li>
                    <li><a href="{{ route('categories.public') }}"
                            class="footer-link">{{ __('navigation.categories') }}</a></li>
                    @guest
                        <li><a href="{{ route('login') }}"
                                class="footer-link">{{ __('navigation.login') }}</a></li>
                    @else
                        <li>
                            <a href="{{ route(auth()->user()->isStaff() ? 'dashboard' : 'anggota.dashboard') }}"
                                class="footer-link">{{ __('footer.my_dashboard') }}</a>
                        </li>
                    @endguest
                </ul>
            </nav>

            <nav aria-label="{{ __('footer.account') }}">
                <span class="block h-1 w-8 rounded-full bg-tertiary/60" aria-hidden="true"></span>
                <p class="mt-3 text-label font-semibold tracking-wide text-primary uppercase">{{ __('footer.account') }}</p>
                <ul class="mt-4 space-y-1 text-sm">
                    @guest
                        <li>
                            <a href="{{ route('login') }}"
                                class="footer-link">{{ __('navigation.login') }}</a>
                        </li>
                        @if (config('perpustakaan.registration.enabled', true))
                            <li>
                                <a href="{{ route('register') }}"
                                    class="footer-link">{{ __('footer.join_member') }}</a>
                            </li>
                        @endif
                    @else
                        @if (auth()->user()->isMember())
                            <li>
                                <a href="{{ route('favorites.index') }}"
                                    class="footer-link">{{ __('navigation.favorites') }}</a>
                            </li>
                            <li>
                                <a href="{{ route('reading-histories.index') }}"
                                    class="footer-link">{{ __('navigation.reading_history') }}</a>
                            </li>
                        @else
                            <li><span class="block px-2 py-1.5 text-secondary">{{ __('footer.signed_in_as', ['role' => auth()->user()->roleLabel()]) }}</span></li>
                        @endif
                    @endguest
                </ul>
            </nav>

            {{--
                Kolom keempat dulu berisi "Jam Layanan" (Senin–Jumat 08.00–16.00)
                + lokasi. Untuk perpustakaan digital jam buka fisik justru
                membingungkan — layanannya tidak tutup. Diganti fakta layanan
                digital yang berlaku di aplikasi ini (akses kapan saja, pinjam
                daring). Lokasi fisik tidak dipakai lagi di footer sama sekali
                (baris alamat di kolom merek juga sudah diganti — lihat catatan
                di kolom tersebut).
            --}}
            <div>
                <span class="block h-1 w-8 rounded-full bg-tertiary/60" aria-hidden="true"></span>
                <p class="mt-3 text-label font-semibold tracking-wide text-primary uppercase">{{ __('footer.digital_service') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li class="flex items-center gap-3 rounded-lg border border-hairline bg-surface px-3 py-2.5 shadow-card">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-md bg-tertiary/10 text-tertiary" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <span class="text-secondary">{{ __('footer.access_247') }}</span>
                    </li>
                    <li class="flex items-center gap-3 rounded-lg border border-hairline bg-surface px-3 py-2.5 shadow-card">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-md bg-tertiary/10 text-tertiary" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </span>
                        <span class="text-secondary">{{ __('footer.digital_borrow') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Baris paling bawah. `built_with` pernah duduk di pojok kanan (sm:justify-between)
             dan tertutup tombol "Kembali ke atas" yang fixed di kanan bawah. Sekarang
             ditumpuk di tengah, jadi tidak pernah bertabrakan dengan tombol itu. --}}
        <div
            class="mt-12 flex flex-col items-center gap-1.5 border-t border-hairline pt-6 text-center text-sm text-secondary">
            <p>&copy; {{ now()->year }} {{ config('app.name') }} &middot; {{ __('footer.tagline') }}</p>
            <p class="text-label">{{ __('footer.built_with', ['version' => Illuminate\Foundation\Application::VERSION]) }}</p>
        </div>
    </div>
</footer>
