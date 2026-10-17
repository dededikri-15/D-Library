    {{--
        Banner ringkasan: gambaran sekilas kondisi peminjaman sebelum tabel.
        Pustakawan tidak perlu menghitung baris kuning/merah satu per satu —
        angka yang paling butuh tindakan (terlambat, menunggu konfirmasi,
        jatuh tempo dekat) muncul paling atas. Tiap baris adalah tautan ke
        filter tabel, supaya angka dan daftar tidak pernah berbeda cerita.
    --}}
    <div class="mt-4 space-y-2">
        @if ($summary['overdue'] > 0)
            <a href="{{ route('loans.index', ['status' => 'overdue']) }}"
               class="flex items-center gap-3 rounded-lg border border-overdue/40 bg-overdue/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-overdue/15 dark:bg-overdue/15">
                <svg class="h-5 w-5 shrink-0 text-overdue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                </svg>
                <span>{{ __('loans.summary_overdue', ['count' => $summary['overdue']]) }}</span>
            </a>
        @endif

        @if ($summary['return_pending'] > 0)
            <a href="{{ route('loans.index', ['active' => 1]) }}"
               class="flex items-center gap-3 rounded-lg border border-borrowed/40 bg-borrowed/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-borrowed/15 dark:bg-borrowed/15">
                <svg class="h-5 w-5 shrink-0 text-borrowed" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                </svg>
                <span>{{ __('loans.summary_return_pending', ['count' => $summary['return_pending']]) }}</span>
            </a>
        @endif

        @if ($summary['due_soon'] > 0)
            <a href="{{ route('loans.index', ['active' => 1]) }}"
               class="flex items-center gap-3 rounded-lg border border-borrowed/40 bg-borrowed/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-borrowed/15 dark:bg-borrowed/15">
                <svg class="h-5 w-5 shrink-0 text-borrowed" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span>{{ __('loans.summary_due_soon', ['count' => $summary['due_soon'], 'days' => $summary['due_soon_days']]) }}</span>
            </a>
        @endif

        @if ($summary['active'] > 0)
            <a href="{{ route('loans.index', ['active' => 1]) }}"
               class="flex items-center gap-3 rounded-lg border border-tertiary/40 bg-tertiary/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-tertiary/15 dark:bg-tertiary/15">
                <svg class="h-5 w-5 shrink-0 text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                </svg>
                <span>{{ __('loans.summary_active', ['count' => $summary['active']]) }}</span>
            </a>
        @endif

        @if ($summary['overdue'] === 0 && $summary['return_pending'] === 0 && $summary['due_soon'] === 0 && $summary['active'] === 0)
            <div class="flex items-center gap-3 rounded-lg border border-available/40 bg-available/10 px-4 py-3 text-sm text-primary dark:bg-available/15">
                <svg class="h-5 w-5 shrink-0 text-available" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span>{{ __('loans.summary_clear') }}</span>
            </div>
        @endif
    </div>
