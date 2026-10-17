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
