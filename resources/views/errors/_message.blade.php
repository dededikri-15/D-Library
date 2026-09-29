@php
    /*
     * Satu partial untuk semua halaman error. Dipakai errors/403, 404, 419,
     * 429, dan 500 supaya pengguna tidak melihat lima halaman dengan gaya
     * berbeda untuk kode yang sebenarnya kasusnya sama.
     */
    [$heading, $message] = match ((int) ($code ?? 0)) {
        403 => ['Halaman tidak diizinkan', 'Anda tidak punya akses ke halaman ini. Hubungi pustakawan bila merasa ini keliru.'],
        404 => ['Halaman tidak ditemukan', 'Alamat yang Anda cari tidak ada, sudah dipindahkan, atau bukunya sudah dihapus.'],
        419 => ['Sesi kedaluwarsa', 'Muat ulang halaman lalu coba lagi.'],
        429 => ['Terlalu banyak permintaan', 'Tunggu sebentar sebelum mencoba kembali.'],
        503 => ['Layanan sedang tidak tersedia', 'Sistem sedang dalam pemeliharaan. Coba lagi beberapa saat lagi.'],
        default => ['Terjadi kesalahan', 'Ada gangguan di sisi kami. Silakan coba beberapa saat lagi.'],
    };
@endphp

<div class="mx-auto max-w-md py-16 text-center">
    <p class="text-display font-semibold text-tertiary">{{ $code }}</p>
    <h1 class="mt-4 text-h1 font-semibold text-primary">{{ $heading }}</h1>
    <p class="mt-3 text-body text-secondary">{{ $message }}</p>

    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke beranda</a>
        <a href="{{ route('books.index') }}" class="btn btn-secondary">Jelajahi katalog</a>
    </div>
</div>
