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
            ['label' => __('home.available_books'), 'value' => $totalBooks, 'tone' => 'available', 'text' => 'text-available'],
            ['label' => __('home.currently_borrowed'), 'value' => $currentlyBorrowed, 'tone' => 'borrowed', 'text' => 'text-borrowed'],
            ['label' => __('home.categories'), 'value' => $totalCategories, 'tone' => 'tertiary', 'text' => 'text-tertiary'],
            ['label' => __('home.registered_members'), 'value' => $totalMembers, 'tone' => 'primary', 'text' => 'text-primary'],
        ] as $stat)
            @php
                $iconPath = match ($stat['tone']) {
                    'available' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                    'borrowed' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'tertiary' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z',
                    default => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                };
            @endphp

            <div class="stat-card">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-label font-medium tracking-wide text-secondary uppercase">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums {{ $stat['text'] }}">
                            {{ number_format($stat['value'], 0, ',', '.') }}
                        </p>
                    </div>

                    <span @class([
                        'stat-icon shrink-0',
                        'bg-available/10 text-available dark:bg-available/15' => $stat['tone'] === 'available',
                        'bg-borrowed/10 text-borrowed dark:bg-borrowed/15' => $stat['tone'] === 'borrowed',
                        'bg-tertiary/10 text-tertiary dark:bg-tertiary/15' => $stat['tone'] === 'tertiary',
                        'bg-secondary/10 text-secondary dark:bg-secondary/15' => $stat['tone'] === 'primary',
                    ])>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                        </svg>
                    </span>
                </div>
            </div>
        @endforeach
    </section>

    {{--
        Grafik aktivitas pribadi anggota (Task 27.3, posisi diminta pengguna).

        Diletakkan tepat di bawah kartu statistik umum supaya anggota langsung
        melihat aktivitasnya sendiri setelah ringkasan perpustakaan, tanpa
        harus menggulir melewati katalog. Grid memakai `xl:grid-cols-2` (bukan
        `lg:`) untuk alasan yang sama dengan grafik staf: di `lg` 12 batang
        berebut ruang sampai label sumbu X tidak terbaca.

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

    {{-- Buku terbaru (umum) --}}
    <section class="mt-14">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="section-eyebrow">{{ __('home.collection') }}</p>
                <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">{{ __('home.latest_books') }}</h2>
                <p class="mt-1.5 text-sm text-secondary">{{ __('home.latest_description') }}</p>
            </div>
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
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-eyebrow">{{ __('home.reader_favorites') }}</p>
                    <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">{{ __('home.popular_books') }}</h2>
                    <p class="mt-1.5 text-sm text-secondary">{{ __('home.popular_description') }}</p>
                </div>
            </div>

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
                <div>
                    <p class="section-eyebrow">{{ __('home.topics') }}</p>
                    <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">{{ __('home.explore_categories') }}</h2>
                    <p class="mt-1.5 text-sm text-secondary">{{ __('home.categories_description') }}</p>
                </div>
                <a href="{{ route('categories.public') }}" class="link-accent text-sm">
                    {{ __('home.all_categories') }} &rarr;
                </a>
            </div>

            <div class="mt-6 flex flex-wrap gap-2.5">
                @foreach ($categories as $category)
                    <a href="{{ route('books.index', ['category' => $category->slug]) }}" class="pill">
                        <svg class="h-4 w-4 text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                        </svg>
                        {{ $category->name }}
                        <span class="rounded-full bg-secondary/10 px-2 py-0.5 text-label tabular-nums text-secondary dark:bg-secondary/20">
                            {{ $category->books_count }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Dasbor khusus anggota --}}
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

            {{--
                Grafik aktivitas pribadi sudah dipindahkan ke atas, tepat di
                bawah kartu statistik umum (permintaan pengguna, Task 27) —
                anggota tidak perlu menggulir sampai sini hanya untuk melihat
                grafiknya.
            --}}

            <div class="card mt-8 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-primary">{{ __('dashboard.currently_borrowed_books') }}</h2>
                    <a href="{{ route('books.index') }}" class="text-sm font-medium text-tertiary hover:underline">
                        {{ __('dashboard.find_books') }} &rarr;
                    </a>
                </div>

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
            </div>

            <div class="card mt-4 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-primary">{{ __('dashboard.loan_history') }}</h2>
                    <a href="{{ route('loans.mine') }}" class="text-sm font-medium text-tertiary hover:underline">
                        {{ __('dashboard.see_all') }} &rarr;
                    </a>
                </div>

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
            </div>

            <div class="card mt-4 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-primary">{{ __('dashboard.reading_history') }}</h2>
                    <a href="{{ route('reading-histories.index') }}" class="text-sm font-medium text-tertiary hover:underline">
                        {{ __('dashboard.see_all') }} &rarr;
                    </a>
                </div>

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
            </div>

            @if ($recommendations->isNotEmpty())
                <div class="mt-8">
                    <h2 class="font-semibold text-primary">{{ __('dashboard.recommendations') }}</h2>
                    <p class="mt-1 text-sm text-secondary">{{ __('dashboard.recommendation_description') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($recommendations as $book)
                            <x-book-card :book="$book" />
                        @endforeach
                    </div>
                </div>
            @endif
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

    {{-- Ajakan bertindak untuk tamu --}}
    @guest
        @if (config('perpustakaan.registration.enabled', true))
            <section class="mt-14 overflow-hidden rounded-xl border border-tertiary/25 bg-tertiary/5 px-6 py-14 text-center">
                <h2 class="text-h1 font-bold tracking-tight text-primary">{{ __('home.start_reading') }}</h2>
                <p class="mx-auto mt-3 max-w-lg text-secondary">
                    {{ __('home.join_description') }}
                </p>
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg mt-8">{{ __('home.register_now') }}</a>
            </section>
        @endif
    @endguest
@endsection