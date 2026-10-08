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

        // Denda per hari keterlambatan, dalam Rupiah. Disimpan sebagai
        // angka bulat (tanpa desimal) karena Rupiah memang tidak memakai
        // pecahan di sini.
        'fine_per_day' => (int) env('PERPUSTAKAAN_LOAN_FINE_PER_DAY', 1000),
    ],

    'display_timezone' => env('PERPUSTAKAAN_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Daftar Tunggu (Waiting List)
    |--------------------------------------------------------------------------
    |
    | Anggota boleh mengantre buku yang sedang habis dipinjam. Begitu buku
    | kembali tersedia, seluruh yang mengantre menerima notifikasi lonceng.
    |
    */

    'waiting_list' => [
        // Batas jumlah buku yang bisa diantre satu anggota sekaligus.
        // Tanpa batas, satu akun bisa mengantre seluruh katalog dan
        // membanjiri loncengnya sendiri saat pengembalian massal.
        'max_per_user' => (int) env('PERPUSTAKAAN_WAITING_LIST_MAX_PER_USER', 5),

        // Berapa jam setelah notifikasi "buku tersedia" sebuah entri
        // dianggap kedaluwarsa dan dihapus. Anggota yang tidak bertindak
        // diberi kesempatan lagi pada ronde berikutnya dengan antre ulang.
        'notify_window_hours' => (int) env('PERPUSTAKAAN_WAITING_LIST_NOTIFY_WINDOW_HOURS', 24),
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
        'cover_max_kb' => (int) env('PERPUSTAKAAN_COVER_MAX_KB', 2048),

        'book_file_mimes' => ['pdf'],
        'book_file_max_kb' => (int) env('PERPUSTAKAAN_BOOK_FILE_MAX_KB', 20480),

        /*
         * Foto profil berdiri sendiri, bukan berbagi dengan cover: user tidak
         * boleh mengunggah PDF ke kolom foto, dan batas ukurannya lebih kecil
         * karena avatar ditampilkan kecil di navbar dan daftar pengguna.
         */
        'avatar_disk' => env('PERPUSTAKAAN_AVATAR_DISK', 'public'),
        'avatar_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'avatar_max_kb' => (int) env('PERPUSTAKAAN_AVATAR_MAX_KB', 2048),
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
