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
