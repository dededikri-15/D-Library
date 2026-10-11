<?php

return [
    'description' => 'Katalog digital untuk menelusuri koleksi, meminjam buku, dan memantau riwayat membaca.',
    'tagline' => 'Katalog Digital',
    'explore' => 'Jelajahi',
    'catalog' => 'Katalog Buku',
    'account' => 'Akun',
    'digital_service' => 'Layanan Digital',
    'access_247' => 'Akses 24 jam, setiap hari',
    'digital_borrow' => 'Pinjam langsung dari katalog',
    'language_note' => 'Tersedia dalam Bahasa Indonesia & English',
    'my_dashboard' => 'Dasbor Saya',
    'join_member' => 'Daftar Anggota',
    'signed_in_as' => 'Masuk sebagai :role',
    'built_with' => 'Dibangun dengan Laravel :version',

    // Statistik kunjungan (lihat CountPublicPageView + FooterStatsComposer).
    // Satu segmen saja sudah cukup: bahasa Indonesia tidak punya bentuk jamak,
    // dan MessageSelector mengembalikan segmen pertama bila hanya ada satu.
    // Bahasa Inggris di lang/en/footer.php yang memakai dua bentuk (time/times).
    'page_views' => 'Halaman ini dibuka :count kali',
    'total_views' => 'Total kunjungan :count',
];
