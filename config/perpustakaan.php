<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aturan Peminjaman
    |--------------------------------------------------------------------------
    |
    | "duration_days" dipakai Loan::dueAt() saat peminjaman dibuat, dan
    | dipakai LoanFactory saat membuat data uji. Ubah nilainya di .env
    | tanpa perlu menyentuh kode.
    |
    */

    'loan' => [
        'duration_days' => (int) env('PERPUSTAKAAN_LOAN_DURATION_DAYS', 14),

        // Batas berapa kali satu peminjaman boleh diperpanjang. Sifatnya
        // tetap: peminjaman tidak boleh diperpanjang tanpa batas, karena
        // orang lain mungkin sedang menunggu buku yang sama.
        'max_renewals' => (int) env('PERPUSTAKAAN_LOAN_MAX_RENEWALS', 1),

        // Sanksi keterlambatan: berapa JAM anggota dilarang meminjam buku
        // setelah pengembalian terlambat diproses. Bukan denda uang —
        // pustaka tidak menagih rupiah, hanya menahan hak pinjam.
        // Default 72 jam (3 hari penuh). Batas akhirnya disimpan di
        // users.suspended_until, jadi tidak ada scheduler yang perlu jalan.
        'suspension_hours' => (int) env('PERPUSTAKAAN_LOAN_SUSPENSION_HOURS', 72),

        // Berapa hari ke depan sebuah peminjaman dianggap "jatuh tempo
        // segera" dan ditampilkan di banner ringkasan halaman peminjaman.
        'due_soon_days' => (int) env('PERPUSTAKAAN_LOAN_DUE_SOON_DAYS', 3),
    ],

    'display_timezone' => env('PERPUSTAKAAN_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Kartu Perpustakaan Digital
    |--------------------------------------------------------------------------
    |
    | Setiap anggota punya satu kartu digital dengan nomor unik dan masa
    | berlaku. Kartu dibuat otomatis saat anggota pertama kali membuka
    | halaman kartunya.
    |
    */

    'library_card' => [
        // Lama berlaku kartu saat PERTAMA KALI dibuat, dalam bulan.
        // Default 12 bulan (1 tahun).
        'validity_months' => (int) env('PERPUSTAKAAN_LIBRARY_CARD_VALIDITY_MONTHS', 12),

        // Tambahan masa berlaku setiap SATU KALI perpanjangan, dalam bulan.
        // Berbeda dari `validity_months`: kartu baru berlaku 1 tahun, tapi
        // perpanjangan hanya menambah 3 bulan — supaya anggota memperpanjang
        // secara berkala, bukan sekali untuk bertahun-tahun.
        'renewal_months' => (int) env('PERPUSTAKAAN_LIBRARY_CARD_RENEWAL_MONTHS', 3),

        // Perpanjangan dilakukan pustakawan. Kartu yang sudah kedaluwarsa
        // lebih dari ini (hari) tidak bisa diperpanjang lagi dan harus
        // dibuat ulang. Default 30 hari.
        'renewal_grace_days' => (int) env('PERPUSTAKAAN_LIBRARY_CARD_RENEWAL_GRACE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registrasi
    |--------------------------------------------------------------------------
    |
    | Role yang diberikan ke user baru. Pendaftaran publik hanya boleh
    | membuat anggota; akun Pustakawan dibuat melalui manajemen pengguna.
    |
    */

    'registration' => [
        'enabled' => (bool) env('PERPUSTAKAAN_REGISTRATION_ENABLED', true),
        'default_role' => 'anggota',
        /*
        |--------------------------------------------------------------------------
        | librarian_registration
        |--------------------------------------------------------------------------
        |
        | Konfigurasi khusus untuk mendaftar sebagai pustakawan. Fitur ini hanya
        | aktif ketika registrasi umum ditutup dan butuh akun staff pertama.
        |
        */

        'librarian_registration' => [
            'enabled' => (bool) env('PERPUSTAKAAN_LIBRARIAN_REGISTRATION_ENABLED', false),
            'secret' => env('LIBRARIAN_REGISTRATION_SECRET', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Penyimpanan Berkas
    |--------------------------------------------------------------------------
    |
    | Cover bersifat publik (tampil di daftar buku), sedangkan file PDF buku
    | WAJIB berada di disk privat dan hanya disajikan lewat controller yang
    | memeriksa peminjaman aktif. Jangan pernah menaruh PDF di disk "public".
    |
    */

    'uploads' => [
        'cover_disk' => env('PERPUSTAKAAN_COVER_DISK', 'public'),
        'book_file_disk' => env('PERPUSTAKAAN_BOOK_FILE_DISK', 'local'),

        'cover_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'cover_max_kb' => (int) env('PERPUSTAKAAN_COVER_MAX_KB', 512),
        'cover_max_width' => (int) env('PERPUSTAKAAN_COVER_MAX_WIDTH', 1200),
        'cover_max_height' => (int) env('PERPUSTAKAAN_COVER_MAX_HEIGHT', 1200),

        'book_file_mimes' => ['pdf'],
        'book_file_max_kb' => (int) env('PERPUSTAKAAN_BOOK_FILE_MAX_KB', 20480),

        /*
         * Foto profil berdiri sendiri, bukan berbagi dengan cover: user tidak
         * boleh mengunggah PDF ke kolom foto, dan batas ukurannya lebih kecil
         * karena avatar ditampilkan kecil di navbar dan daftar pengguna.
         */
        'avatar_disk' => env('PERPUSTAKAAN_AVATAR_DISK', 'public'),
        'avatar_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'avatar_max_kb' => (int) env('PERPUSTAKAAN_AVATAR_MAX_KB', 256),
        'avatar_max_width' => (int) env('PERPUSTAKAAN_AVATAR_MAX_WIDTH', 400),
        'avatar_max_height' => (int) env('PERPUSTAKAAN_AVATAR_MAX_HEIGHT', 400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Keamanan
    |--------------------------------------------------------------------------
    |
    | Semua angka batas ada di sini supaya tidak ada nilai yang ditulis
    | dua kali di tempat berbeda ( LoginRequest, AppServiceProvider, routes).
    |
    */

    'security' => [
        // Batas percobaan login GAGAL per kombinasi email + IP, per menit.
        'login_max_attempts' => (int) env('PERPUSTAKAAN_LOGIN_MAX_ATTEMPTS', 5),
        // Pengaman luar untuk endpoint login (termasuk percobaan BERHASIL).
        'login_per_minute' => (int) env('PERPUSTAKAAN_LOGIN_PER_MINUTE', 10),
        // Mencegah pembuatan akun massal lewat form registrasi publik.
        'register_per_minute' => (int) env('PERPUSTAKAAN_REGISTER_PER_MINUTE', 6),
        // Mencegah pengambilan PDF massal dengan satu akun.
        'file_read_per_minute' => (int) env('PERPUSTAKAAN_FILE_READ_PER_MINUTE', 30),
        // Mencegah satu orang mengetik nonstop di pencarian hidup katalog dan
        // membebani database dengan query `like` dari karakter demi karakter.
        'search_per_minute' => (int) env('PERPUSTAKAAN_SEARCH_PER_MINUTE', 60),
        // Mencegah email bombing lewat form lupa sandi (permintaan reset per IP).
        'password_per_minute' => (int) env('PERPUSTAKAAN_PASSWORD_PER_MINUTE', 3),

        /*
        |--------------------------------------------------------------------------
        | CAPTCHA Configuration
        |--------------------------------------------------------------------------
        |
        | Konfigurasi Cloudflare Turnstile untuk verifikasi di sisi server.
        |
        */

        'captcha' => [
            'enabled' => (bool) env('PERPUSTAKAAN_CAPTCHA_ENABLED', false),
            'site_key' => env('TURNSTILE_SITE_KEY', ''),
            'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => (int) env('PERPUSTAKAAN_PER_PAGE', 12),
    ],

];
