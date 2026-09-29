<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tugas Terjadwal
|--------------------------------------------------------------------------
|
| `overdue` bukan sesuatu yang bisa dihitung sekali lalu disimpan: waktu
| terus berjalan, jadi status itu harus disegarkan secara berkala. Jadwal
| di bawah membuatnya tetap benar walau tidak ada yang membuka halaman
| peminjaman. Tanpa scheduler, `loans.status` hanya benar sampai seseorang
| membuka halaman yang memanggil MarkOverdueLoans.
|
| `hourly()`, bukan `daily()`: buku bisa terlambat pada jam 00:01 dan
| pustakawan baru melihatnya jam 08:00. Dengan `hourly()` selisihnya cuma
| satu jam; dengan `daily()` satu malam penuh salah label.
|
| CATATAN untuk dev lokal: scheduler Laravel TIDAK jalan otomatis.
| Setelah `php artisan migrate` di project ini, jalankan juga:
|
|     php artisan schedule:work
|
| Catatan: halaman peminjaman tetap benar karena controller memanggil
| MarkOverdueLoans secara langsung sebelum menampilkan tabel.
|
*/

Schedule::command('loans:mark-overdue')->hourly();
