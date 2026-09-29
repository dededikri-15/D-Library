@props([
    'status' => null,
])

@php
    /*
    | Satu sumber kebenaran untuk warna + label status, dipakai kartu buku,
    | detail buku, dan tabel peminjaman. Dulu rangkap tiga di tiap view.
    |
    | Perhatikan: yang dipilih di sini adalah class komponen (`.badge-*`),
    | bukan utility warna seperti `text-available`. Class komponen itu yang
    | sudah tahu harus menaikkan opacity-nya di dark mode — warna terang di
    | atas latar gelap dengan opasitas 10% praktis tidak terlihat.
    */
    $meta = match ($status) {
        App\Models\Book::STATUS_AVAILABLE => ['label' => 'Tersedia', 'class' => 'badge-available'],
        App\Models\Book::STATUS_BORROWED => ['label' => 'Dipinjam', 'class' => 'badge-borrowed'],
        App\Models\Book::STATUS_INACTIVE => ['label' => 'Tidak aktif', 'class' => 'badge-muted'],
        App\Models\Loan::STATUS_RETURNED => ['label' => 'Dikembalikan', 'class' => 'badge-available'],
        App\Models\Loan::STATUS_OVERDUE => ['label' => 'Terlambat', 'class' => 'badge-overdue'],
        default => ['label' => (string) ($status ?: 'Tidak diketahui'), 'class' => 'badge-muted'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$meta['class']]) }}>
    <span class="inline-block h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $meta['label'] }}
</span>
