<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifikasi dalam aplikasi (lonceng di topbar + halaman riwayat).
 *
 * Seluruh aksi di sini mengambil user dari `$request->user()`, bukan dari
 * parameter URL: notifikasi adalah data privat per orang, dan relasi
 * `notifications()` sudah bermorf ke pemiliknya. Tidak ada satu pun route
 * di sini yang bisa menjangkau notifikasi milik orang lain — termasuk saat
 * id notifikasi orang lain diketik manual ke address bar, karena query-nya
 * selalu dibatasi `where('notifiable_id', user login)`.
 */
class NotificationController extends Controller
{
    /**
     * Riwayat notifikasi milik user yang sedang login.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('notifications.index', [
            'notifications' => $user->notifications()->paginate(15)->withQueryString(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca, lalu lanjut ke tujuannya.
     *
     * Kenapa GET dan bukan POST? Tautan notifikasi harus bisa dibuka tab
     * baru dan klik tengah seperti tautan biasa, dan JS tidak boleh
     * menjadi syarat agar notifikasi bisa ditandai. Menandai "sudah dibaca"
     * bersifat idempoten dan tidak merusak apa pun kalau kebetulan ikut
     * terpanggil oleh prefetch — karena itu risikonya setara dengan GET
     * yang tidak mengubah data.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $stored = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $stored->markAsRead();

        return redirect($this->destination($stored->data['url'] ?? null));
    }

    /**
     * Tandai seluruh notifikasi yang belum dibaca sekaligus.
     *
     * Satu UPDATE untuk semua baris, bukan loop `markAsRead()`: daftar bisa
     * berisi ratusan baris dan tiap baris butuh query sendiri.
     *
     * Ada dua pemanggil: tombol "Tandai semua" di halaman (form biasa, mau
     * redirect) dan lonceng di topbar yang dibuka lewat JS (mau JSON, supaya
     * angkanya bisa dihapus di tempat tanpa memuat ulang halaman — refresh
     * akan menutup panel yang baru saja dibuka).
     */
    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('notifications.mark_all_done')]);
        }

        return back()->with('status', __('notifications.mark_all_done'));
    }

    /**
     * Tujuan setelah notifikasi dibuka.
     *
     * `url` diisi oleh server lewat helper `route()`, jadi praktis selalu
     * milik aplikasi ini. Tapi karena kolomnya teks bebas, tetap dibatasi
     * pada URL yang diawali alamat aplikasi — sekadar pengaman bila isi
     * kolom ini pernah berubah karena migrasi data atau bug di masa depan.
     * Kalau tidak cocok, user tetap mendarat di halaman notifikasi, bukan
     * di situs asing.
     */
    private function destination(mixed $url): string
    {
        if (is_string($url) && $url !== '' && str_starts_with($url, url('/'))) {
            return $url;
        }

        return route('notifications.index');
    }
}
