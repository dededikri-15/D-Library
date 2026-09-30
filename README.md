# D-Library — Perpustakaan Digital

Aplikasi katalog dan peminjaman perpustakaan digital, dibangun dengan
Laravel 12. Manuskrip bisa dibaca, dipinjam, dan dikembalikan, dan yang punya
peran **Pustakawan** mengelola seluruh katalog, data penulis/penerbit, serta
pengguna.

Proyek ini portofolio: tujuannya menunjukkan cara membangun aplikasi Laravel
yang benar-benar selesai dan aman, bukan sekadar kerangka.

---

## Daftar Isi

- [Teknologi](#teknologi)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Menjalankan Proyek](#menjalankan-proyek)
- [Akun Uji](#akun-uji)
- [Peran dan Izin](#peran-dan-izin)
- [Fitur](#fitur)
- [Struktur Proyek](#struktur-proyek)
- [Skema Basis Data](#skema-basis-data)
- [Pengujian](#pengujian)
- [Catatan Keamanan](#catatan-keamanan)
- [Keputusan Desain yang Menarik](#keputusan-design-yang-menarik)

---

## Teknologi

| Lapisan | Pilihan |
| --- | --- |
| Framework | Laravel 12 |
| Bahasa | PHP 8.4 |
| Basis data | PostgreSQL 14+ |
| Frontend | Blade + TailwindCSS v4 + Vanilla JavaScript |
| Build asset | Vite 7 |
| Pengujian | PHPUnit 11 (Feature test) |
| Format kode | Laravel Pint |

Tidak ada React, Vue, atau Angular. Tidak ada CSS framework selain Tailwind.
Tidak ada paket npm runtime di luar toolchain build. Every bit of interactivity
adalah Vanilla JS yang bisa dibaca.

## Kebutuhan Sistem

- PHP **8.4** (composer.json mengizinkan `^8.2`, tapi proyek ini dikembangkan
  dan diuji di 8.4)
- Composer 2
- Node.js 20+ dan npm
- PostgreSQL 14 atau lebih baru, dengan database bernama `db_perpustakaan`

> **SQLite tidak didukung untuk development.** Skema, index, dan typing proyek
> ini ditulis untuk PostgreSQL. Test suite tetap memakai SQLite in-memory
> supaya cepat — jadi kedua DB akan kamu temui, tapi hanya PostgreSQL yang
> didukung penuh di dev.

## Menjalankan Proyek

```bash
# 1. Dependensi + .env + key + migrate + build asset, semuanya sekaligus
composer setup
```

Kalau `composer setup` terasa terlalu magical, jalankan manual:

```bash
composer install
cp .env.example .env          # di Windows: copy .env.example .env
php artisan key:generate

# Isi kredensial PostgreSQL di .env, lalu:
createdb db_perpustakaan
php artisan migrate --seed
php artisan storage:link      # wajib: cover buku butuh symlink public/storage

npm install
npm run build
```

### Mode pengembangan

```bash
composer dev
```

Menjalankan empat hal sekaligus dengan nama proses yang berbeda: web server
(`php artisan serve`), queue worker, log tailer (`pail`), dan Vite dengan HMR.
Kalau hanya ingin CSS/JS yang rebuild otomatis, `npm run dev` saja sudah cukup.

### Konfigurasi

Semua angka yang bisa diubah ada di `.env`, tidak ada nilai yang ditulis dua
kali di beberapa tempat. Lihat `config/perpustakaan.php` — itu satu-satunya
tempat semua aturan ini dibaca dari:

| Variabel | Default | Arti |
| --- | --- | --- |
| `PERPUSTAKAAN_LOAN_DURATION_DAYS` | `14` | Lama peminjaman sebelum jatuh tempo |
| `PERPUSTAKAAN_DISPLAY_TIMEZONE` | `Asia/Jakarta` | Zona waktu tampilan tanggal |
| `PERPUSTAKAAN_REGISTRATION_ENABLED` | `true` | Izinkan pendaftaran publik |
| `PERPUSTAKAAN_COVER_MAX_KB` | `2048` | Batas ukuran cover (gambar) |
| `PERPUSTAKAAN_BOOK_FILE_MAX_KB` | `20480` | Batas ukuran file PDF buku |
| `PERPUSTAKAAN_PER_PAGE` | `12` | Jumlah buku per halaman |
| `PERPUSTAKAAN_LOGIN_MAX_ATTEMPTS` | `5` | Gagal login sebelum dikunci |
| `PERPUSTAKAAN_LOGIN_PER_MINUTE` | `10` | Batas request login per menit |
| `PERPUSTAKAAN_REGISTER_PER_MINUTE` | `6` | Batas pendaftaran per menit |
| `PERPUSTAKAAN_FILE_READ_PER_MINUTE` | `30` | Batas unduh PDF per menit |
| `PERPUSTAKAAN_SEARCH_PER_MINUTE` | `60` | Batas pencarian per menit |

## Akun Uji

`php artisan db:seed` membuat empat akun. Password semuanya **`password`**:

| Email | Peran |
| --- | --- |
| `pustakawan@perpustakaan` | Pustakawan |
| `anggota1@perpustakaan.test` | Anggota |

Kredensial ini sengaja weakened dan hanya untuk pengembangan. Jangan pernah
membiarkannya di server yang bisa dijangkau publik.

## Peran dan Izin

Ada dua peran, dan bedanya disengaja: **Pustakawan** mengelola seluruh fungsi
staff termasuk pengguna; **Anggota** hanya berinteraksi dengan katalog
dan peminjamannya sendiri.

```
Pustakawan
├── Dashboard (statistik peminjaman)
├── Katalog buku        ├── borrow/mark inactive
├── Kategori, Penulis, Penerbit   (CRUD penuh)
├── Manajemen pengguna  (CRUD penuh)
└── Peminjaman          (overview, return, perpanjangan)

Anggota
├── Katalog & pencarian  (baca)
├── Detail buku + baca online
├── Peminjaman saya     (borrow, return)
├── Favorit
└── Riwayat baca
```

Route staff dilindungi `role:pustakawan` (middleware di
`bootstrap/app.php`, karena Laravel 12 tidak punya `Http/Kernel.php`).

## Fitur

**Katalog**
- Daftar buku dengan filter kategori, penulis, penerbit, dan status
- Pencarian hidup dengan pratinjau hasil, tanpa reload
- Detail buku dengan manuscript terkait, dan pembaca PDF bawaan browser

**Pinjaman**
- Peminjaman dengan validasi stok dan tanggal kembali yang dihitung server
- Pengembalian dan perpanjangan
- Status otomatis `borrowed` / `returned` / `overdue`
- Larangan pinjam ganda, dicek dalam transaksi dengan row lock (lihat di bawah)

**Yurisdiksi Pustakawan**
- CRUD penuh untuk buku, kategori, penulis, penerbit, dan pengguna
- Upload cover (gambar) dan file PDF (PDF saja), dengan penghapusan file lama
  otomatis saat diganti

**Anggota**
- Favorit dengan toggle tanpa reload (AJAX, progressive enhancement)
- Riwayat baca dengan progressive tracking
- Dashboard anggota

## Struktur Proyek

```
app/
├── Http/Controllers/          # BookController, LoanController, FavoriteController, ...
│   └── Concerns/
│       └── RespondsToAjax.php # satu tempat untuk dual JSON/redirect
├── Http/Middleware/           # EnsureUserHasRole
├── Http/Requests/             # validasi per-form, bukan di controller
├── Models/                    # User, Book, Category, Author, Publisher, Loan, ...
├── Policies/                  # otorisasi per model
└── Support/
    └── Json.php               # helper respons JSON

resources/
├── css/app.css                # token desain + komponen (.modal, .toast, .skeleton-*, ...)
├── js/app.js                  # seluruh interaksi, Vanilla JS
└── views/
    ├── components/            # <x-modal>, <x-dropdown>, <x-toast-region>, ...
    ├── vendor/pagination/     # override pagination::tailwind
└── ...                    # Blade per fitur

tests/Feature/                 # 26 file feature test
storage/app/
└── private/books/             # PDF buku, TIDAK di public
```

## Skema Basis Data

Delapan tabel inti:

| Tabel | Isi |
| --- | --- |
| `users` | Akun + `role` (`pustakawan` / `anggota`) |
| `categories` | Kategori buku |
| `authors` | Penulis |
| `publishers` | Penerbit |
| `books` | Buku, termasuk jumlah dan status peminjaman |
| `loans` | Riwayat peminjaman + status |
| `favorites` | Relasi anggota → buku (unik per pasangan) |
| `reading_histories` | Riwayat baca anggota → buku |

Semuanya punya foreign key dan index yang sesuai. `books.status` hanya bisa
`available` / `borrowed` / `inactive`; `loans.status` hanya `borrowed` /
`returned` / `overdue`. Manuskrip buku tidak pernah ada di disk publik — hanya
disajikan lewat controller yang memeriksa peminjaman aktif.

## Pengujian

```bash
php artisan test                        # seluruh suite
php artisan test --filter=BookCatalogTest
php artisan test tests/Feature/SecurityTest.php
composer test                           # sama, tapi config dibersihkan dulu
```

**367 test / 1.231 assertion, semuanya lulus.** Test suite memakai SQLite
in-memory (lihat `phpunit.xml`), bukan PostgreSQL — supaya cepat dan tidak
menyentuh database dev. Konsekuensinya ada jebakan yang harus diingat:

- Query PostgreSQL-only akan lolos di dev lalu gagal di test.
- SQLite lebih permisif: query yang dia toleransi bisa tetap lempar error di
  PostgreSQL. Contoh nyata: route `/buku/abc` pernah sampai ke
  `where id = 'abc'` — SQLite mengembalikan `null` (hasilnya 404 yang bersih),
  sementara PostgreSQL menolak teks untuk kolom `bigint` (hasilnya 500).

  Perbaikannya: setiap route yang mengambil parameter model memakai
  `->whereNumber()` di `routes/web.php`, jadi id non-numerik ditolak di lapisan
  routing dan tidak pernah sampai ke SQL.

## Catatan Keamanan

- **Password** di-hash dengan bcrypt; tidak ada user yang bisa punya role
  lebih tinggi dari yang diberikan sistem.
- **Rate limiting** pada login, registrasi, unduh PDF, dan pencarian.
- **Lockout** setelah beberapa kali login gagal per kombinasi email + IP.
- **Upload divalidasi dua lapis**: MIME (gambar untuk cover, PDF untuk
  manuskrip) + ukuran + nama file dibuat ulang. File lama dihapus saat diganti.
- **PDF tidak pernah menyentuh disk publik.** Hanya lewat route
  terautentikasi yang memeriksa peminjaman aktif, dan route-nya punya rate limit.
- **Semua input divalidasi** lewat Form Request, bukan inline di controller.
- **CSRF** pada semua request yang mengubah state.

## Keputusan Desain yang Menarik

Beberapa keputusan di proyek ini yang mungkin kelihatan tidak biasa:

**Peminjaman ganda dicek di dalam transaksi dengan row lock, bukan read-then-write.**
`if ($book->status === 'available') { $book->update([...]); }` akan gagal
diam-diam kalau dua orang menekan tombol pinjam di detik yang sama — keduanya
baca `available`, keduanya menulis, dan salah satu dapat lends away buku yang
sudah dipinjam. Yang dipakai `app/Actions/BorrowBook.php` adalah
`DB::transaction()` + `lockForUpdate()` pada baris `books`, lalu pengecekan
pinjaman aktif di dalam transaksi itu. Row lock diambil pada `books` (bukan
`loans`) karena peminjamannya berbalik pada buku, bukan pada record `loans`.

**Pagination di-override, bukan cuma diberi warna.**
`pagination::tailwind` bawaan menyembunyikan nomor halaman di bawah 640px. Di
katalog dengan puluhan halaman, pengguna HP jadi tidak punya cara lain tahu
dia sedang di halaman berapa. Override-nya ada di
`resources/views/vendor/pagination/tailwind.blade.php`.

**Tailwind dibangun tanpa bergantung pada cache Blade.**
`@source` diarahkan hanya ke file yang dilacak git. Kalau `@source` diarahkan
ke `storage/framework/views` (yang gitignored), output build bisa berubah
menurut keadaan cache mesin — build jadi tidak reproducible.
`tests/Feature/BuildIntegrityTest` menjaga aturan ini. Dua jebakan lain yang
diajaga test yang sama: jangan menulis glob `@source` di dalam komentar CSS
(glob itu menutup blok komentar lebih awal), dan jangan menyebut nama class
utility di dalam komentar (CSS dan Blade sama-sama dipindai Tailwind, jadi class
yang cuma disebut di komentar tetap masuk build).


