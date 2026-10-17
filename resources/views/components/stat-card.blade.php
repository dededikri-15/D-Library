@props([
    'label',
    'value',
    'tone' => 'primary',
    // false = tanpa ikon (dipakai di kartu ringkasan riwayat peminjaman anggota).
    'icon' => true,
])

{{--
    Kartu statistik: label kecil di atas, angka besar di bawah, ikon di kanan.
    Warna ikut `$tone` (available/borrowed/tertiary/primary) supaya makna
    warnanya sama di semua halaman — hijau = tersedia, kuning = dipinjam.
    Angka memakai pemisah titik (1.000) sesuai format Indonesia.
--}}

@php
    $tones = [
        'available' => [
            'text' => 'text-available',
            'icon' => 'bg-available/10 text-available dark:bg-available/15',
            'path' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
        ],
        'borrowed' => [
            'text' => 'text-borrowed',
            'icon' => 'bg-borrowed/10 text-borrowed dark:bg-borrowed/15',
            'path' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'overdue' => [
            'text' => 'text-overdue',
            'icon' => 'bg-overdue/10 text-overdue dark:bg-overdue/15',
            'path' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
        ],
        'tertiary' => [
            'text' => 'text-tertiary',
            'icon' => 'bg-tertiary/10 text-tertiary dark:bg-tertiary/15',
            'path' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z',
        ],
        'primary' => [
            'text' => 'text-primary',
            'icon' => 'bg-secondary/10 text-secondary dark:bg-secondary/15',
            'path' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        ],
    ];

    $tone = $tones[$tone] ?? $tones['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-label font-medium tracking-wide text-secondary uppercase">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold tabular-nums {{ $tone['text'] }}">
                {{ number_format($value, 0, ',', '.') }}
            </p>
        </div>

        @if ($icon)
            <span class="stat-icon shrink-0 {{ $tone['icon'] }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tone['path'] }}"/>
                </svg>
            </span>
        @endif
    </div>
</div>
