<?php

/*
| Notifikasi dalam aplikasi (lonceng + halaman riwayat).
|
| Teks di sini dibaca SAAT notifikasi ditampilkan (bukan saat dibuat),
| sehingga pemberitahuan yang masuk saat user berbahasa Indonesia ikut
| berubah ketika dia ganti ke English — tidak ada pesan yang tersimpan
| dalam dua bahasa di database. Lihat `components/notification-item.blade.php`
| untuk cara placeholder-nya diisi dari payload.
*/

return [
    'title' => 'Notifikasi',
    'description' => 'Pemberitahuan peminjaman, pengembalian, keterlambatan, dan jejak aksi Anda.',
    'menu_label' => 'Notifikasi',
    'bell_label' => 'Buka menu notifikasi',
    'bell_unread' => ':count notifikasi belum dibaca',
    'bell_none' => 'tidak ada yang belum dibaca',
    'view_all' => 'Lihat semua notifikasi',
    'mark_all' => 'Tandai semua sudah dibaca',
    'mark_all_done' => 'Semua notifikasi ditandai sudah dibaca.',
    'unread_count' => ':count belum dibaca',
    'unread_marker' => 'belum dibaca',
    'empty_short' => 'Belum ada notifikasi',
    'empty_title' => 'Belum ada notifikasi',
    'empty_description' => 'Pemberitahuan peminjaman, pengembalian, keterlambatan, dan jejak aksi Anda akan muncul di sini.',
    'generic_title' => 'Notifikasi baru',
    'fine_notice' => 'Denda sementara Rp :fine.',

    'reasons' => [
        'admin_deleted' => 'catatan dihapus oleh pustakawan',
        'unknown' => 'alasan tidak tercatat',
    ],

    /*
    | Satu judul + satu kalimat per jenis notifikasi. Parameter berupa
    | penanda `:nama` yang diisi dari payload — tanggal sudah diformat,
    | denda sudah dirupiahkan, dan alasan sudah diterjemahkan dari kode.
    */
    'types' => [
        'borrowed' => [
            'title' => 'Berhasil meminjam buku',
            'body' => 'Anda meminjam :book_title. Kembalikan sebelum :due_at.',
        ],
        'loan_created' => [
            'title' => 'Peminjaman baru tercatat',
            'body' => ':user_name meminjam :book_title pada :borrowed_at, tenggat :due_at.',
        ],
        'approved' => [
            'title' => 'Peminjaman disetujui',
            'body' => 'Peminjaman :book_title tercatat pada :borrowed_at. Kembalikan sebelum :due_at.',
        ],
        'rejected' => [
            'title' => 'Peminjaman dibatalkan',
            'body' => 'Peminjaman :book_title dibatalkan. Alasan: :reason.',
        ],
        'return_requested' => [
            'title' => 'Permintaan pengembalian',
            'body' => ':user_name mengajukan pengembalian :book_title (tenggat :due_at).',
        ],
        'returned' => [
            'title' => 'Buku telah dikembalikan',
            'body' => ':book_title dikembalikan pada :returned_at.',
            'fine_notice' => 'Denda Rp :fine tercatat pada pengembalian ini.',
        ],
        'due_soon' => [
            'title' => 'Tenggat mendekat',
            'body' => ':book_title harus dikembalikan pada :due_at.',
            'renewals' => 'Sisa perpanjangan: :renewals_left.',
        ],
        'overdue_member' => [
            'title' => 'Peminjaman terlambat',
            'body' => ':book_title terlambat dikembalikan :overdue_days hari (tenggat :due_at).',
            'fine_notice' => 'Denda sementara Rp :fine.',
        ],
        'overdue_staff' => [
            'title' => 'Keterlambatan pengembalian',
            'body' => ':user_name terlambat mengembalikan :book_title sejak :due_at — :overdue_days hari.',
            'fine_notice' => 'Denda sementara Rp :fine.',
        ],
        'book_available' => [
            'title' => 'Buku yang Anda tunggu tersedia',
            'body' => ':book_title kembali tersedia dan siap dipinjam. Buruan sebelum diambil orang lain.',
        ],

        /*
        | Jejak aksi (ActionLogged): dikirim ke PELAKUNYA sendiri ketika
        | dia menambah, mengubah, atau menghapus data. Placeholder `:subject`
        | berisi nama objek yang disentuh (judul buku, nama kategori, dst.).
        */
        'profile_updated' => [
            'title' => 'Profil diperbarui',
            'body' => 'Data profil Anda diperbarui.',
        ],
        'password_changed' => [
            'title' => 'Kata sandi diganti',
            'body' => 'Kata sandi akun Anda berhasil diganti.',
        ],
        'avatar_replaced' => [
            'title' => 'Foto profil diperbarui',
            'body' => 'Foto profil Anda berhasil diperbarui.',
        ],
        'avatar_removed' => [
            'title' => 'Foto profil dihapus',
            'body' => 'Foto profil Anda dihapus. Avatar kini mengikuti jenis kelamin.',
        ],
        'book_created' => [
            'title' => 'Buku ditambahkan',
            'body' => 'Buku :subject berhasil ditambahkan ke katalog.',
        ],
        'book_updated' => [
            'title' => 'Buku diperbarui',
            'body' => 'Perubahan pada buku :subject tersimpan.',
        ],
        'book_deleted' => [
            'title' => 'Buku dihapus',
            'body' => 'Buku :subject dihapus dari katalog.',
        ],
        'category_created' => [
            'title' => 'Kategori ditambahkan',
            'body' => 'Kategori :subject berhasil ditambahkan.',
        ],
        'category_updated' => [
            'title' => 'Kategori diperbarui',
            'body' => 'Perubahan pada kategori :subject tersimpan.',
        ],
        'category_deleted' => [
            'title' => 'Kategori dihapus',
            'body' => 'Kategori :subject dihapus.',
        ],
        'author_created' => [
            'title' => 'Penulis ditambahkan',
            'body' => 'Penulis :subject berhasil ditambahkan.',
        ],
        'author_updated' => [
            'title' => 'Penulis diperbarui',
            'body' => 'Perubahan pada penulis :subject tersimpan.',
        ],
        'author_deleted' => [
            'title' => 'Penulis dihapus',
            'body' => 'Penulis :subject dihapus.',
        ],
        'publisher_created' => [
            'title' => 'Penerbit ditambahkan',
            'body' => 'Penerbit :subject berhasil ditambahkan.',
        ],
        'publisher_updated' => [
            'title' => 'Penerbit diperbarui',
            'body' => 'Perubahan pada penerbit :subject tersimpan.',
        ],
        'publisher_deleted' => [
            'title' => 'Penerbit dihapus',
            'body' => 'Penerbit :subject dihapus.',
        ],
        'user_created' => [
            'title' => 'Pengguna ditambahkan',
            'body' => 'Pengguna :subject berhasil ditambahkan.',
        ],
        'user_updated' => [
            'title' => 'Pengguna diperbarui',
            'body' => 'Perubahan pada pengguna :subject tersimpan.',
        ],
        'user_deleted' => [
            'title' => 'Pengguna dihapus',
            'body' => 'Pengguna :subject dihapus.',
        ],
        'favorite_added' => [
            'title' => 'Ditambahkan ke favorit',
            'body' => ':subject ditambahkan ke daftar favorit Anda.',
        ],
        'favorite_removed' => [
            'title' => 'Dihapus dari favorit',
            'body' => ':subject dihapus dari daftar favorit Anda.',
        ],
        'waiting_list_joined' => [
            'title' => 'Masuk daftar tunggu',
            'body' => 'Anda mengantre buku :subject. Kami akan memberi tahu begitu tersedia.',
        ],
        'waiting_list_left' => [
            'title' => 'Keluar daftar tunggu',
            'body' => 'Anda membatalkan antrean buku :subject.',
        ],
        'reading_saved' => [
            'title' => 'Posisi baca tersimpan',
            'body' => 'Posisi baca :subject tersimpan di halaman :last_page.',
        ],
        'reading_removed' => [
            'title' => 'Riwayat baca dihapus',
            'body' => 'Riwayat baca :subject dihapus.',
        ],
        'loan_recorded' => [
            'title' => 'Peminjaman tercatat',
            'body' => 'Anda mencatat peminjaman :subject atas nama :user_name.',
        ],
        'loan_renewed' => [
            'title' => 'Peminjaman diperpanjang',
            'body' => 'Tenggat :subject diperpanjang hingga :due_at.',
        ],
        'return_recorded' => [
            'title' => 'Pengembalian tercatat',
            'body' => 'Pengembalian :subject tercatat pada :returned_at.',
        ],
        'return_requested_self' => [
            'title' => 'Permintaan pengembalian terkirim',
            'body' => 'Permintaan pengembalian :subject dikirim ke pustakawan (tenggat :due_at).',
        ],
        'fine_paid' => [
            'title' => 'Denda ditandai lunas',
            'body' => 'Denda untuk :subject (Rp :fine) ditandai sudah dibayar.',
        ],
        'loan_record_deleted' => [
            'title' => 'Catatan peminjaman dihapus',
            'body' => 'Catatan peminjaman :subject dihapus.',
        ],
        'mail_deleted' => [
            'title' => 'Email dihapus',
            'body' => 'Email bersubjek :subject dihapus dari kotak masuk.',
        ],
    ],
];
