            {{-- Metadata --}}
            <dl class="card mt-7 divide-y divide-hairline overflow-hidden">
                @foreach ([
                    __('book.publisher') => $book->publisher?->name ?? '-',
                    __('book.isbn') => $book->isbn,
                    __('book.pages') => $book->pages ?? '-',
                    __('book.copies_label') => __('book.copies_available', ['available' => $book->available_copies_count, 'total' => $book->copies_count]),
                    __('book.readers') => number_format($readerCount, 0, ',', '.'),
                    __('book.status') => match ($book->status) {
                        App\Models\Book::STATUS_AVAILABLE => __('book.available_for_loan'),
                        App\Models\Book::STATUS_BORROWED => __('book.currently_borrowed'),
                        default => __('book.inactive'),
                    },
                ] as $label => $value)
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                        <dt class="text-sm text-secondary">{{ $label }}</dt>
                        <dd @class([
                            'text-sm font-medium text-primary',
                            // ISBN selalu angka, jadi font monospace memudahkan
                            // dikelompokkan per 4 digit saat diketik ulang.
                            'font-mono tabular-nums' => $label === 'ISBN',
                            'tabular-nums' => $label === 'Jumlah pembaca',
                        ])>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
