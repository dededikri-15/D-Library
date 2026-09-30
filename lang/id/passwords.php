<?php

/*
|--------------------------------------------------------------------------
| Pesan Reset Kata Sandi
|--------------------------------------------------------------------------
|
| `ForgotPasswordController` dan `ResetPasswordController` meneruskan status
| dari password broker ke tampilan lewat `__($status)`, jadi nama kuncinya
| ikut mengikuti locale aplikasi.
|
*/

return [

    'reset' => 'Kata sandi berhasil diubah.',
    'sent' => 'Tautan reset kata sandi telah dikirim ke email Anda.',
    'throttled' => 'Terlalu banyak percobaan. Silakan coba lagi dalam :seconds detik.',
    'token' => 'Tautan reset kata sandi tidak valid atau sudah kedaluwarsa.',
    'user' => 'Kami tidak menemukan pengguna dengan alamat email tersebut.',

];
