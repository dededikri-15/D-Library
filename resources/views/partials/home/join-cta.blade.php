            {{--
                Panel ajakan memakai gradasi `bg-hero` yang sama dengan hero di
                atas, supaya beranda punya dua "titik berat" visual yang seragam;
                glowing mengikuti token --hero-glow sehingga otomatis ikut tema
                terang/gelap. Chip manfaat menjawab pertanyaan "dapet apa kalau
                daftar" tepat di sebelah tombolnya.
            --}}
            <section class="relative mt-14 overflow-hidden rounded-xl border border-tertiary/25 bg-hero">
                <span class="pointer-events-none absolute -top-24 -right-16 h-64 w-64 rounded-full blur-3xl" style="background-color: var(--hero-glow)" aria-hidden="true"></span>
                <span class="pointer-events-none absolute -bottom-28 -left-20 h-64 w-64 rounded-full blur-3xl" style="background-color: var(--hero-glow)" aria-hidden="true"></span>

                <div class="relative mx-auto max-w-2xl px-6 py-14 text-center sm:px-10 sm:py-16">
                    <p class="section-eyebrow animate-fade-in">{{ __('home.membership_eyebrow') }}</p>

                    <h2 class="mt-4 text-h1 font-bold tracking-tight text-balance text-primary animate-fade-up">
                        {{ __('home.start_reading') }}
                    </h2>

                    <p class="mx-auto mt-3 max-w-xl leading-relaxed text-secondary animate-fade-up [animation-delay:80ms]">
                        {{ __('home.join_description') }}
                    </p>

                    <ul class="mt-7 flex flex-wrap items-center justify-center gap-2.5" role="list">
                        @foreach ([__('home.cta_favorites'), __('home.cta_history'), __('home.cta_loans')] as $benefit)
                            {{--
                                Ikon centang dulunya `text-tertiary` polos: di atas chip
                                gelap warnanya terbaca nyaris hitam. Sekarang centangnya
                                putih (`text-primary`) di dalam bulatan lembut
                                `bg-tertiary/25`, jadi tetap kontras di tema terang
                                maupun gelap tanpa memakai warna status.
                            --}}
                            <li class="inline-flex items-center gap-2 rounded-full border border-hairline-strong bg-surface/70 py-1.5 pr-3.5 pl-2 text-sm font-medium text-primary shadow-card">
                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-tertiary/25" aria-hidden="true">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                </span>
                                {{ $benefit }}
                            </li>
                        @endforeach
                    </ul>

                    {{--
                        Tombol "Daftar sekarang"/"Masuk" sengaja TIDAK diulang di sini.
                        Keduanya sudah ada di hero paling atas; mengulangnya membuat
                        halaman punya dua pasang tombol identik. CTA ini berfungsi
                        sebagai penutup ajakan (manfaat + balasan ke hero lewat link
                        "Jelajahi katalog"), bukan pintu kedua.
                    --}}
                    <p class="mt-7">
                        <a href="{{ route('books.index') }}" class="link-accent text-sm">{{ __('home.explore_catalog') }} &uarr;</a>
                    </p>
                </div>
            </section>
