<?php

namespace App\Http\Controllers;

use App\Models\LibraryCard;
use App\Models\User;
use App\Notifications\CardRenewRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryCardController extends Controller
{
    /**
     * Halaman kartu perpustakaan digital milik anggota yang sedang login.
     *
     * Kartu dibuat saat pertama kali dibuka (`createFor` memakai
     * `firstOrCreate`, jadi klik ganda atau request bersamaan aman).
     * Tidak ada route untuk melihat kartu orang lain — setiap anggota
     * hanya berhak melihat kartunya sendiri.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        /*
         * Kartu hanya bisa dibuat jika tanggal lahir sudah terisi — nomor
         * kartu (DDMMYY) membutuhkan tanggal lahir. Jika belum, view akan
         * menampilkan pesan "lengkapi profil dulu" alih-alih kartu rusak.
         */
        $card = $user->hasBirthDate()
            ? LibraryCard::createFor($user)
            : null;

        return view('library-cards.show', [
            'card' => $card,
        ]);
    }

    /**
     * Anggota mengajukan perpanjangan masa berlaku kartunya.
     *
     * Ini hanya MENGAJUKAN, bukan memperpanjang: seluruh pustakawan
     * mendapat notifikasi lonceng (tautan ke halaman edit pengguna anggota
     * ini), dan pustakawan yang menekan tombol perpanjang seperti biasa.
     *
     * Syarat diperiksa ulang di sini — tombol memang hanya dirender kalau
     * semua syarat hijau, tapi view bisa dilewati (curl, tab lama), jadi
     * keputusan akhir tetap di server.
     */
    public function requestRenewal(Request $request): RedirectResponse
    {
        $user = $request->user();

        $card = $user->hasBirthDate()
            ? LibraryCard::createFor($user)
            : null;

        if ($card === null) {
            return $this->backWithStatus(__('messages.card_no_birthdate'));
        }

        if ($card->hasRenewalRequest()) {
            return $this->backWithStatus(__('messages.card_renew_already_requested'));
        }

        if (! $card->canRenew()) {
            return back()->with('error', __('messages.card_renew_requirements_unmet'));
        }

        $card->update(['renewal_requested_at' => now()]);

        User::query()
            ->pustakawan()
            ->get()
            ->each(fn (User $librarian) => $librarian->notify(new CardRenewRequested($card)));

        return $this->backWithStatus(__('messages.card_renew_requested'));
    }

    /**
     * Daftar pengajuan perpanjangan kartu yang menunggu konfirmasi
     * (halaman khusus pustakawan, route `library-cards.requests`).
     *
     * Pustakawan cukup membuka satu halaman ini untuk melihat SELURUH
     * anggota yang sedang menunggu — tanpa harus mengingat satu per satu
     * dari lonceng notifikasi atau halaman edit tiap pengguna.
     */
    public function requests(): View
    {
        $cards = LibraryCard::query()
            ->whereNotNull('renewal_requested_at')
            ->with('user')
            ->orderBy('renewal_requested_at')
            ->paginate(config('perpustakaan.pagination.per_page'))
            ->withQueryString();

        return view('library-cards.requests', [
            'cards' => $cards,
        ]);
    }

    /**
     * Perpanjang masa berlaku kartu anggota (dipanggil pustakawan).
     *
     * Route berada di grup `role:pustakawan`, jadi anggota tidak pernah
     * bisa memperpanjang kartunya sendiri. Pustakawan menekan tombol ini
     * setelah memeriksa checklist syarat di halaman edit pengguna.
     */
    public function renew(Request $request, User $user): RedirectResponse
    {
        ['card' => $card, 'error' => $error] = $this->validateRenewal($user);

        if ($card === null) {
            // "Syarat belum terpenuhi" adalah penolakan (merah, halaman edit
            // pengguna); sisanya hanya info status (kembali ke halaman sebelumnya).
            if ($error === 'card_not_renewable') {
                return $this->failure('users.edit', __('messages.'.$error), ['user' => $user]);
            }

            return $this->backWithStatus(__('messages.'.$error));
        }

        $card->renew();

        $this->notifySelf(
            $request->user(),
            'card_renewed',
            ['subject' => $user->name, 'months' => LibraryCard::renewalMonths()],
            route('users.edit', $user),
        );

        return $this->success(
            'users.edit',
            __('messages.card_renewed', ['months' => LibraryCard::renewalMonths()]),
            ['user' => $user],
        );
    }

    /**
     * Konfirmasi pengajuan perpanjangan dari halaman daftar pengajuan.
     *
     * Aturannya identik dengan `renew()` di atas — hanya halaman tujuan
     * setelah berhasil yang berbeda: pustakawan kembali ke daftar
     * pengajuan (bukan halaman edit pengguna), jadi dia bisa langsung
     * memproses pengajuan berikutnya tanpa navigasi ulang.
     */
    public function confirmRenewal(Request $request, User $user): RedirectResponse
    {
        ['card' => $card, 'error' => $error] = $this->validateRenewal($user);

        if ($card === null) {
            if ($error === 'card_not_renewable') {
                return back()->with('error', __('messages.'.$error));
            }

            return $this->backWithStatus(__('messages.'.$error));
        }

        $card->renew();

        $this->notifySelf(
            $request->user(),
            'card_renewed',
            ['subject' => $user->name, 'months' => LibraryCard::renewalMonths()],
            route('library-cards.requests'),
        );

        return $this->backWithStatus(
            __('messages.card_renewed', ['months' => LibraryCard::renewalMonths()]),
        );
    }

    /**
     * Periksa semua prasyarat perpanjangan untuk satu anggota.
     *
     * Berhasil → `card` terisi kartu siap diperpanjang. Gagal → `error`
     * berisi kunci pesan di `lang/{locale}/messages.php`. Dipakai bersama
     * oleh `renew()` dan `confirmRenewal()` supaya keduanya tidak mungkin
     * menerapkan syarat yang berbeda.
     *
     * @return array{card: LibraryCard|null, error: string|null}
     */
    private function validateRenewal(User $user): array
    {
        if ($user->isStaff()) {
            return ['card' => null, 'error' => 'card_not_member'];
        }

        // Kartu anggota dibuat saat pertama kali dibuka. Kalau belum ada
        // (anggota belum pernah buka halaman kartu) buat dulu, supaya
        // pustakawan tetap bisa memperpanjang tanpa meminta anggota
        // membuka halaman kartunya lebih dulu.
        $card = $user->hasBirthDate()
            ? LibraryCard::createFor($user)
            : null;

        if ($card === null) {
            return ['card' => null, 'error' => 'card_no_birthdate'];
        }

        // Syarat diperiksa ULANG di server. Tombol di view memang sudah
        // disabled kalau syarat belum terpenuhi, tapi view bisa
        // dilewati (curl, tab lama, dsb.) — keputusan akhir selalu di sini.
        if (! $card->canRenew()) {
            return ['card' => null, 'error' => 'card_not_renewable'];
        }

        return ['card' => $card, 'error' => null];
    }
}
