<?php

/*
|--------------------------------------------------------------------------
| Pesan Validasi Bahasa Indonesia
|--------------------------------------------------------------------------
|
| Aplikasi berjalan dengan `APP_LOCALE=id`. Tanpa file ini, Translator tidak
| menemukan kuncinya dan mengembalikan kunci mentah apa adanya — pengguna
| melihat literal `validation.required` di layar, bukan kalimat.
|
| Aturan yang tidak ada di bawah ini akan jatuh ke nama field mentah
| (`validation.xyz`), jadi daftarnya harus lengkap untuk semua aturan yang
| dipakai aplikasi. Nama field (`judul`, `nama kategori`, dst) dikirim satu per
| form lewat `attributes()` di masing-masing Form Request.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Kolom :attribute harus disetujui.',
    'active_url' => 'Kolom :attribute bukan URL yang valid.',
    'after' => 'Kolom :attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Kolom :attribute harus berupa larik.',
    'ascii' => 'Kolom :attribute hanya boleh berisi karakter ascii dan angka.',
    'before' => 'Kolom :attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Kolom :attribute harus berisi antara :min sampai :max item.',
        'file' => 'Ukuran :attribute harus antara :min sampai :max kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
        'string' => 'Kolom :attribute harus berisi antara :min sampai :max karakter.',
    ],
    'boolean' => 'Kolom :attribute harus bernilai ya atau tidak.',
    'can' => 'Kolom :attribute mengandung nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Kolom :attribute tidak mengandung nilai yang diperlukan.',
    'current_password' => 'Kata sandi yang dimasukkan salah.',
    'date' => 'Kolom :attribute bukan tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus berisi tanggal yang sama dengan :date.',
    'date_format' => 'Kolom :attribute tidak sesuai format :format.',
    'decimal' => 'Kolom :attribute harus memiliki :decimal digit di belakang koma.',
    'declined' => 'Kolom :attribute harus ditolak.',
    'declined_if' => 'Kolom :attribute harus ditolak ketika :other bernilai :value.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits angka.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max angka.',
    'dimensions' => 'Kolom :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Kolom :attribute memiliki nilai yang duplikat.',
    'doesnt_end_with' => 'Kolom :attribute tidak boleh diakhiri salah satu dari: :values.',
    'doesnt_start_with' => 'Kolom :attribute tidak boleh diawali salah satu dari: :values.',
    'email' => 'Format :attribute tidak valid.',
    'ends_with' => 'Kolom :attribute harus diakhiri salah satu dari: :values.',
    'enum' => 'Nilai :attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'extensions' => 'Kolom :attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute harus diisi.',
    'gt' => [
        'array' => 'Kolom :attribute harus berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute harus lebih dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai lebih dari :value.',
        'string' => 'Kolom :attribute harus berisi lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Kolom :attribute harus berisi minimal :value item.',
        'file' => 'Ukuran :attribute harus minimal :value kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai minimal :value.',
        'string' => 'Kolom :attribute harus berisi minimal :value karakter.',
    ],
    'hex_color' => 'Kolom :attribute harus berupa warna heksadesimal yang valid.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => 'Kolom :attribute tidak ada di dalam :other.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa string JSON yang valid.',
    'list' => 'Kolom :attribute harus berupa larik.',
    'lowercase' => 'Kolom :attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => 'Kolom :attribute harus berisi kurang dari :value item.',
        'file' => 'Ukuran :attribute harus kurang dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai kurang dari :value.',
        'string' => 'Kolom :attribute harus berisi kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Kolom :attribute tidak boleh lebih dari :value item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :value kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih dari :value.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :value karakter.',
    ],
    'mac_address' => 'Kolom :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh lebih dari :max item.',
        'file' => 'Ukuran :attribute maksimal :max kilobyte.',
        'numeric' => 'Kolom :attribute maksimal :max.',
        'string' => 'Kolom :attribute maksimal :max karakter.',
    ],
    'max_digits' => 'Kolom :attribute tidak boleh lebih dari :max angka.',
    'mimes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus berisi minimal :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute minimal :min.',
        'string' => 'Kolom :attribute minimal :min karakter.',
    ],
    'min_digits' => 'Kolom :attribute harus terdiri dari minimal :min angka.',
    'multiple_of' => 'Kolom :attribute harus berupa kelipatan dari :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Kolom :attribute harus mengandung minimal satu huruf.',
        'mixed' => 'Kolom :attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => 'Kolom :attribute harus mengandung minimal satu angka.',
        'symbols' => 'Kolom :attribute harus mengandung minimal satu simbol.',
        'uncompromised' => 'Kolom :attribute pernah muncul dalam kebocoran data. Pilih yang lain.',
    ],
    'present' => 'Kolom :attribute wajib ada.',
    'prohibited' => 'Kolom :attribute dilarang diisi.',
    'prohibited_if' => 'Kolom :attribute dilarang diisi ketika :other bernilai :value.',
    'prohibited_if_accepted' => 'Kolom :attribute dilarang diisi ketika :other disetujui.',
    'prohibited_if_declined' => 'Kolom :attribute dilarang diisi ketika :other ditolak.',
    'prohibited_unless' => 'Kolom :attribute dilarang diisi kecuali :other bernilai :value.',
    'prohibits' => 'Kolom :attribute dilarang diisi bila ada isian pada :other.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Kolom :attribute harus punya isian: :values.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other bernilai :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi ketika :other disetujui.',
    'required_if_declined' => 'Kolom :attribute wajib diisi ketika :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai :value.',
    'required_with' => 'Kolom :attribute wajib diisi bila :values ada.',
    'required_with_all' => 'Kolom :attribute wajib diisi bila :values ada.',
    'required_without' => 'Kolom :attribute wajib diisi bila :values tidak ada.',
    'required_without_all' => 'Kolom :attribute wajib diisi bila :values tidak ada.',
    'same' => 'Kolom :attribute dan :other harus sama.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus terdiri dari :size karakter.',
    ],
    'starts_with' => 'Kolom :attribute harus diawali salah satu dari: :values.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'timezone' => 'Kolom :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => 'Kolom :attribute gagal diunggah.',
    'upper' => 'Kolom :attribute harus berupa huruf besar.',
    'url' => 'Format :attribute tidak valid. Pastikan diawali http:// atau https://.',
    'ulid' => 'Kolom :attribute harus berupa ULID yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan Kustom
    |--------------------------------------------------------------------------
    |
    | Aturan khusus (misalnya validasi ISBN di `BookRequest`) memakai
    | `ValidationException::withMessages()`, bukan kunci di sini.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nama Field Bawaan
    |--------------------------------------------------------------------------
    |
    | Dipakai kalau sebuah form tidak mengirim `attributes()` sendiri. Hampir
    | semua Form Request di aplikasi ini sudah punya nama Indonesia-nya sendiri.
    |
    */

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'role' => 'role',
        'title' => 'judul',
        'isbn' => 'ISBN',
        'description' => 'deskripsi',
        'publication_year' => 'tahun terbit',
        'pages' => 'jumlah halaman',
        'initial_copies' => 'jumlah eksemplar',
        'add_copies' => 'jumlah eksemplar tambahan',
        'status' => 'status',
        'category_id' => 'kategori',
        'author_id' => 'penulis',
        'publisher_id' => 'penerbit',
        'cover' => 'cover',
        'file' => 'file buku',
        'photo' => 'foto',
        'biography' => 'biografi',
        'slug' => 'slug',
        'address' => 'alamat',
        'website' => 'situs web',
        'phone' => 'telepon',
        'user_id' => 'anggota',
        'book_id' => 'buku',
        'borrowed_at' => 'tanggal pinjam',
        'last_page' => 'halaman terakhir',
        'locale' => 'bahasa',
    ],

];
