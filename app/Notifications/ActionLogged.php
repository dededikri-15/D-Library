<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi "aksi tercatat" — jejak tindakan yang dikirim ke PELAKUNYA
 * sendiri, bukan ke orang lain.
 *
 * Kenapa satu kelas untuk banyak jenis aksi: puluhan aksi (CRUD buku,
 * kategori, penulis, penerbit, pengguna, profil, favorit, riwayat baca,
 * perpanjangan, denda, ...) punya bentuk payload yang sama — kode tipe,
 * beberapa parameter teks, dan satu tautan tujuan. Membuat satu kelas
 * per aksi hanya menghasilkan 30 file isapan jempol yang isinya sama.
 *
 * Kontrak payload mengikuti notifikasi peminjaman yang sudah ada:
 * - `type`  — KODE tipe (mis. `book_created`). Teks judul/badan TIDAK
 *   disimpan; dibaca dari `lang/{locale}/notifications.php` saat ditampilkan.
 * - parameter lain (`subject`, `book_title`, `user_name`, `due_at`, ...)
 *   diisi dari array `$data` yang diberikan pemanggil.
 * - `url`   — tujuan setelah notifikasi diklik (lihat NotificationController).
 *
 * `subject` dipakai oleh hampir semua tipe aksi CRUD sebagai nama objek
 * yang disentuh (judul buku, nama kategori, dst.) supaya badan kalimat
 * bisa memakai placeholder `:subject` yang sama.
 */
class ActionLogged extends Notification
{
    use Queueable;

    /**
     * @param  string  $type  kode tipe, mis. `book_created` — harus ada di `notifications.php:types`
     * @param  array<string, mixed>  $data  parameter tambahan untuk placeholder
     * @param  string|null  $url  tujuan klik notifikasi; null = halaman notifikasi
     */
    public function __construct(
        public string $type,
        public array $data = [],
        public ?string $url = null,
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            ...$this->data,
            'url' => $this->url ?? url('/'),
        ];
    }
}
