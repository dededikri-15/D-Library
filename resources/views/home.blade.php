@extends('layouts.app')

@section('title', $user ? ($isStaff ? __('dashboard.librarian_title') : __('dashboard.member_greeting', ['name' => $user->name])) : config('app.name'))

@section('content')
    {{-- Hero --}}
    <section class="bg-hero relative overflow-hidden rounded-xl border border-hairline">
        <span class="pointer-events-none absolute -top-24 -right-16 h-72 w-72 rounded-full blur-3xl" style="background-color: var(--hero-glow)" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-32 -left-20 h-72 w-72 rounded-full blur-3xl" style="background-color: var(--hero-glow)" aria-hidden="true"></span>

        <div class="relative px-6 py-16 sm:px-10 sm:py-20 lg:px-16 lg:py-24">
            <p class="section-eyebrow animate-fade-in">D-Library</p>

            <h1 class="mt-5 max-w-3xl text-display font-extrabold tracking-tight text-balance text-primary animate-fade-up">
                {{ __('home.headline') }} <span class="text-tertiary">{{ __('home.headline_accent') }}</span>
            </h1>

            <p class="mt-5 max-w-xl text-lg leading-relaxed text-secondary animate-fade-up [animation-delay:80ms]">
                {{ __('home.description') }}
            </p>

            <div class="mt-9 flex flex-wrap items-center gap-3 animate-fade-up [animation-delay:160ms]">
                @auth
                    <a href="{{ route('books.index') }}" class="btn btn-secondary btn-lg">{{ __('home.explore_catalog') }}</a>
                @else
                    <a href="{{ route('books.index') }}" class="btn btn-blue-primary btn-lg">
                        {{ __('home.explore_catalog') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-blue-outline btn-lg">{{ __('home.sign_in') }}</a>
                    @if (config('perpustakaan.registration.enabled', true))
                        <a href="{{ route('register') }}" class="btn btn-blue-outline btn-lg">{{ __('home.register') }}</a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

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
            <section class="mt-14">
                <x-dashboard.header :title="__('dashboard.member_greeting', ['name' => $user->name])" :description="__('dashboard.member_description')" />
            </section>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    __('dashboard.active_loans') => [$activeLoanCount, 'text-primary'],
                    __('dashboard.overdue') => [$overdueCount, 'text-overdue'],
                    __('dashboard.favorites') => [$favoriteCount, 'text-primary'],
                ] as $label => [$value, $tone])
                    <div class="card p-5">
                        <p class="text-label font-medium tracking-wide text-secondary uppercase">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-semibold {{ $tone }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <x-panel class="mt-8" :title="__('dashboard.currently_borrowed_books')" :href="route('books.index')" :link-label="__('dashboard.find_books')">

                @forelse ($activeLoans as $loan)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                        <a href="{{ route('books.show', $loan->book) }}" class="font-medium text-primary transition-colors hover:text-tertiary">
                            {{ $loan->book->title }}
                        </a>
                        <div class="flex items-center gap-3">
                            @if ($loan->isOverdue())
                                <x-status-badge :status="'overdue'" />
                            @else
                                <x-status-badge :status="$loan->status" />
                            @endif
                            <span class="text-sm text-secondary">{{ __('dashboard.due_date', ['date' => $loan->displayDate($loan->due_at)?->format('d M Y')]) }}</span>
                        </div>
                    </div>
                @empty
                    <x-empty-state class="mt-4 border-0" :title="__('dashboard.no_active_books')" :description="__('dashboard.browse_to_borrow')">
                        <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">{{ __('dashboard.browse_catalog') }}</a>
                    </x-empty-state>
                @endforelse
            </x-panel>

            <x-panel class="mt-4" :title="__('dashboard.loan_history')" :href="route('loans.mine')" :link-label="__('dashboard.see_all')">

                @forelse ($recentLoansMember as $loan)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                        <div class="min-w-0">
                            <a href="{{ route('books.show', $loan->book) }}" class="font-medium text-primary transition-colors hover:text-tertiary">
                                {{ $loan->book->title }}
                            </a>
                            <p class="text-sm text-secondary">{{ __('dashboard.borrowed_on', ['date' => $loan->displayDate($loan->borrowed_at)?->format('d M Y')]) }}</p>
                        </div>
                        <x-status-badge :status="$loan->status" />
                    </div>
                @empty
                    <p class="mt-4 text-sm text-secondary">Belum ada riwayat peminjaman.</p>
                @endforelse
            </x-panel>

            <x-panel class="mt-4" :title="__('dashboard.reading_history')" :href="route('reading-histories.index')" :link-label="__('dashboard.see_all')">

                @forelse ($readingHistory as $history)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                        <div class="min-w-0">
                            <a href="{{ route('books.show', $history->book) }}" class="font-medium text-primary transition-colors hover:text-tertiary">
                                {{ $history->book->title }}
                            </a>
                            <p class="text-sm text-secondary">{{ __('dashboard.page', ['number' => $history->last_page]) }}</p>
                        </div>
                        @if ($history->book->hasFile() && $history->last_page > 0)
                            <a href="{{ route('books.read', ['book' => $history->book, 'page' => $history->last_page]) }}" class="btn btn-secondary btn-sm">{{ __('dashboard.continue_reading') }}</a>
                        @endif
                    </div>
                @empty
                    <x-empty-state class="mt-4 border-0" :title="__('dashboard.no_reading_history')" :description="__('dashboard.open_digital_book')" />
                @endforelse
            </x-panel>
        @endif

        {{-- Dasbor khusus pustakawan --}}
        @if ($isStaff)
            <section class="mt-14">
                <x-dashboard.header :title="__('dashboard.librarian_title')" :description="__('dashboard.librarian_description')" />
            </section>

            <div class="mt-6">
                <x-dashboard.stats :total-books="$totalBooksStaff" :total-users="$totalUsers" :total-loans="$totalLoans" :total-members="$totalMembersStaff" :active-loans="$activeLoansStaff" :overdue-loans="$overdueLoansStaff" :book-status="$bookStatus">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">{{ __('dashboard.manage_users') }}</a>
                    <a href="{{ route('loans.index') }}" class="btn btn-secondary btn-sm">{{ __('dashboard.manage_loans') }}</a>
                    <a href="{{ route('books.create') }}" class="btn btn-secondary btn-sm">{{ __('dashboard.add_book') }}</a>
                    <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm">{{ __('dashboard.book_list') }}</a>
                </x-dashboard.stats>
            </div>

            {{--
                Laporan grafik (Task 26.3).

                Grafik bulanan butuh lebar penuh untuk 12 batang, jadi
                diletakkan sendiri di baris atas bersama dua peringkat.
                `xl:` (bukan `lg:`) karena di `lg` grafik dan dua peringkat
                akan berebut ruang di satu baris dan label sumbu X mengecil
                sampai tidak terbaca.
            --}}
            <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <x-dashboard.monthly-chart :items="$loansPerMonth"
                                           :title="__('dashboard.loans_chart_title')" />

                <div class="grid content-start gap-6">
                    <x-dashboard.rank-bars :items="$topBorrowedBooks" type="book"
                                           :title="__('dashboard.top_books')" />
                    <x-dashboard.rank-bars :items="$topBorrowers" type="member"
                                           :title="__('dashboard.top_members')" />
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card p-5">
                    <h2 class="font-semibold text-primary">{{ __('dashboard.recent_loans') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentLoans as $loan)
                            <div class="flex items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                                <div class="min-w-0">
                                    <a href="{{ route('books.show', $loan->book) }}" class="block truncate font-medium text-primary hover:text-tertiary">
                                        {{ $loan->book->title }}
                                    </a>
                                    <p class="text-sm text-secondary">{{ $loan->user->name }}</p>
                                </div>
                                <x-status-badge :status="$loan->status" />
                            </div>
                        @empty
                            <p class="text-sm text-secondary">{{ __('dashboard.no_loans') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-semibold text-primary">{{ __('dashboard.recent_books') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentBooks as $book)
                            <div class="flex items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                                <div class="min-w-0">
                                    <a href="{{ route('books.show', $book) }}" class="block truncate font-medium text-primary hover:text-tertiary">
                                        {{ $book->title }}
                                    </a>
                                    <p class="text-sm text-secondary">{{ $book->author?->name ?? '-' }}</p>
                                </div>
                                <x-status-badge :status="$book->status" />
                            </div>
                        @empty
                            <p class="text-sm text-secondary">{{ __('dashboard.no_books') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-semibold text-primary">{{ __('dashboard.recent_activity') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentActivity as $activity)
                            <div class="flex items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-primary">{{ $activity->user->name }}</p>
                                    <p class="text-sm text-secondary">{{ __('dashboard.reading_activity', ['title' => $activity->book->title]) }}</p>
                                </div>
                                <span class="shrink-0 text-sm text-secondary">{{ $activity->last_read_at?->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-secondary">{{ __('dashboard.no_activity') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
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
        <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-dashboard.monthly-chart :items="$myLoansPerMonth"
                                       :title="__('dashboard.my_loans_chart_title')"
                                       empty-description-key="dashboard.my_loans_chart_empty_description" />

            <x-dashboard.monthly-chart :items="$myReadingStartsPerMonth"
                                       :title="__('dashboard.reading_chart_title')"
                                       total-key="dashboard.reading_chart_total"
                                       alt-key="dashboard.reading_chart_alt"
                                       empty-title-key="dashboard.reading_chart_empty"
                                       empty-description-key="dashboard.reading_chart_empty_description"
                                       column-key="dashboard.chart_reading" />
        </div>
    @endif

    {{--
        Koleksi buku (umum) — dikelompokkan rapat setelah blok dasbor
        (permintaan pengguna). Urutannya: Buku Terbaru → Buku Populer →
        Kategori, tanpa jarak kosong yang memisahkan fitur buku dari
        bagian lain.
    --}}

    {{-- Buku terbaru (umum) --}}
    <section class="mt-14">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <x-section-heading
                :eyebrow="__('home.collection')"
                :title="__('home.latest_books')"
                :description="__('home.latest_description')" />
            <a href="{{ route('books.index') }}" class="link-accent text-sm">
                {{ __('home.see_all') }} &rarr;
            </a>
        </div>

        @if ($latestBooks->isEmpty())
            <x-empty-state class="mt-6" :title="__('home.empty_catalog')" :description="__('home.empty_catalog_description')" />
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Populer (umum) --}}
    @if ($popularBooks->isNotEmpty() && $popularBooks->first()->loans_count > 0)
        <section class="mt-14">
            <x-section-heading
                :eyebrow="__('home.reader_favorites')"
                :title="__('home.popular_books')"
                :description="__('home.popular_description')" />

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($popularBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Kategori (umum) --}}
    @if ($categories->isNotEmpty())
        <section class="mt-14">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <x-section-heading
                    :eyebrow="__('home.topics')"
                    :title="__('home.explore_categories')"
                    :description="__('home.categories_description')" />
                <a href="{{ route('categories.public') }}" class="link-accent text-sm">
                    {{ __('home.all_categories') }} &rarr;
                </a>
            </div>

            {{--
                Grid kartu, bukan baris pill: pill membungkus secara acak sehingga
                baris terakhir sering menyisakan ruang kosong yang janggal. Kartu
                memakai lebar sama rata, jadi jumlah buku tetap terbaca jelas dan
                area klik lebih besar di layar sentuh.

                Jeda animasi ditulis sebagai style inline, bukan
                `[animation-delay:{{ ... }}ms]`: Tailwind memindai file sebagai
                teks, jadi class yang dirangkai Blade tidak pernah menghasilkan CSS.
            --}}
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($categories as $category)
                    <a href="{{ route('books.index', ['category' => $category->slug]) }}"
                       class="group card flex items-center gap-3.5 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-tertiary/40 hover:shadow-lift focus-visible:ring-2 focus-visible:ring-tertiary/40 focus-visible:outline-none motion-reduce:hover:transform-none animate-fade-up"
                       style="animation-delay: {{ $loop->index * 40 }}ms">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-tertiary/10 text-tertiary transition-colors duration-300 group-hover:bg-tertiary/20">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z"/>
                            </svg>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-primary">{{ $category->name }}</span>
                            <span class="mt-0.5 block text-sm text-secondary">
                                {{ __('categories.book_count', ['count' => $category->books_count]) }}
                            </span>
                        </span>

                        <svg class="h-4 w-4 shrink-0 -translate-x-1 text-tertiary opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100 motion-reduce:transition-none"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Ajakan bertindak untuk tamu --}}
    @guest
        @if (config('perpustakaan.registration.enabled', true))
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
        @endif
    @endguest
@endsection
