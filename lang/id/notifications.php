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
    'description' => 'Pemberitahuan peminjaman, pengembalian, dan keterlambatan.',
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
    'empty_description' => 'Pemberitahuan peminjaman, pengembalian, dan keterlambatan akan muncul di sini.',
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
    ],
];
