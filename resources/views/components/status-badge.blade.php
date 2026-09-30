@props([
    'status' => null,
])

@php
    /*
    | Satu sumber kebenaran untuk warna + label status, dipakai kartu buku,
    | detail buku, dan tabel peminjaman. Dulu rangkap tiga di tiap view.
    |
    | Labelnya diambil dari `statusOptions()` di model, bukan ditulis ulang di
    | sini. Dua daftar label pasti akan berbeda begitu atau begitu — dan yang
    | terlihat akibatnya bukan cuma dropdown, tapi badge yang kontrannya beda
    | dengan isi dropdown di halaman yang sama.
    |
    | Perhatikan: yang dipilih di sini adalah class komponen (`.badge-*`),
    | bukan utility warna seperti `text-available`. Class komponen itu yang
    | sudah tahu harus menaikkan opacity-nya di dark mode — warna terang di
    | atas latar gelap dengan opasitas 10% praktis tidak terlihat.
    */
    $meta = match ($status) {
        App\Models\Book::STATUS_AVAILABLE => ['class' => 'badge-available'],
        App\Models\Book::STATUS_BORROWED => ['class' => 'badge-borrowed'],
        App\Models\Book::STATUS_INACTIVE => ['class' => 'badge-muted'],
        App\Models\Loan::STATUS_RETURNED => ['class' => 'badge-available'],
        App\Models\Loan::STATUS_OVERDUE => ['class' => 'badge-overdue'],
        default => ['class' => 'badge-muted'],
    };

    $label = $status
        ? (App\Models\Book::statusOptions()[$status] ?? App\Models\Loan::statusOptions()[$status] ?? $status)
        : 'Tidak diketahui';
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$meta['class']]) }}>
    <span class="inline-block h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $label }}
</span>
