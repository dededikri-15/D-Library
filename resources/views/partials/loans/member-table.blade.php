    {{--
        `min-w-[1000px]`: tanpa lebar minimum, tabel mengecil mengikuti
        lebar layar dan teks di tiap sel membungkus (judul jadi 3 baris)
        sehingga `.table-wrap` tidak pernah meluap dan scroll horizontal
        tidak pernah muncul. Dengan lebar minimum, tabel lebih lebar dari
        bungkusnya pada layar sempit → scroll bar muncul, isi tetap satu
        baris seperti tabel Pengguna.
    --}}
    <div class="table-wrap mt-6">
        <table class="table min-w-[1000px]">
            <thead>
                <tr>
                    <th>{{ __('loans.book') }}</th>
                    <th>{{ __('loans.copy') }}</th>
                    <th>{{ __('loans.borrowed_at') }}</th>
                    <th>{{ __('loans.due_at') }}</th>
                    <th>{{ __('loans.returned_at') }}</th>
                    <th>{{ __('loans.status') }}</th>
                    <th class="text-right">{{ __('loans.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="font-medium text-primary">
                            @if ($loan->book)
                                <a href="{{ route('books.show', $loan->book) }}"
                                    class="link-accent">{{ $loan->book->title }}</a>
                            @else
                                <span class="text-secondary">{{ __('loans.book_deleted') }}</span>
                            @endif
                        </td>
                        <td class="font-mono text-secondary">{{ $loan->bookCopy?->inventory_code ?? '-' }}</td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->borrowed_at)?->format('d M Y') }}</td>
                        <td class="text-secondary">
                            {{ $loan->displayDate($loan->due_at)?->format('d M Y') }}
                            @if ($loan->isOverdue())
                                <span class="block text-label text-overdue">
                                    {{ __('loans.late_since', ['date' => $loan->displayDate($loan->due_at)?->diffForHumans()]) }}
                                </span>
                            @elseif ($loan->isActive() && $loan->renewalsLeft() > 0)
                                <span class="block text-label text-secondary">
                                    {{ __('loans.renewals_left', ['count' => $loan->renewalsLeft()]) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->returned_at)?->format('d M Y') ?? '-' }}
                        </td>
                        <td>
                            <x-status-badge :status="$loan->status" />
                            {{-- Pengembalian yang terlambat menandai peminjaman
                                 ini sebagai pemicu sanksi 72 jam — anggota perlu
                                 melihatnya di baris ini, bukan hanya menebak dari
                                 pesan flash yang hilang saat refresh. --}}
                            @if ($loan->isLateReturn())
                                <span class="mt-1 block text-label text-overdue">
                                    {{ __('loans.late_return_note', ['hours' => (int) config('perpustakaan.loan.suspension_hours')]) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($loan->isReturned())
                                    <span class="text-label text-secondary">{{ __('loans.done') }}</span>
                                @elseif ($loan->hasReturnRequest())
                                    <span class="text-label font-medium text-borrowed">{{ __('loans.waiting_librarian') }}</span>
                                @elseif ($loan->isActive() && $loan->book)
                                    @if ($loan->canRenew())
                                        <form method="POST" action="{{ route('loans.mine.renew', $loan) }}"
                                            data-confirm="{{ __('loans.renew_confirmation', ['title' => $loan->book->title, 'date' => $loan->displayDate($loan->renewalDueAt())?->format('d M Y')]) }}"
                                            data-confirm-title="{{ __('loans.renew') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm">{{ __('loans.renew') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('loans.mine.request-return', $loan) }}"
                                        data-confirm="{{ __('loans.request_confirmation', ['title' => $loan->book->title]) }}"
                                        data-confirm-title="{{ __('loans.request_return') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">{{ __('loans.request_return') }}</button>
                                    </form>
                                @elseif ($loan->book)
                                    <a href="{{ route('books.show', $loan->book) }}" class="btn btn-ghost btn-sm">
                                        {{ __('book.details') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-0">
                            <x-empty-state class="border-0" :title="__('loans.empty_history')"
                                :description="__('loans.empty_history_description')">
                                <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">{{ __('loans.open_catalog') }}</a>
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
