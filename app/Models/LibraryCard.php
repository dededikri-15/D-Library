<?php

namespace App\Models;

use Database\Factories\LibraryCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryCard extends Model
{
    /** @use HasFactory<LibraryCardFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_number',
        'valid_until',
        'renewal_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'renewal_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nomor kartu untuk user tertentu.
     *
     * Format: DDMMYY dari tanggal lahir (contoh: lahir 15 Jun 1995 → 150695).
     * Deterministik — satu user selalu menghasilkan nomor yang sama.
     *
     * Membutuhkan `date_of_birth` sudah terisi; jika belum, throw exception
     * karena tidak ada dasar untuk membentuk nomor.
     */
    public static function numberFor(User $user): string
    {
        if (! $user->hasBirthDate()) {
            throw new \RuntimeException(
                'Tanggal lahir wajib diisi sebelum nomor kartu perpustakaan bisa dibuat.',
            );
        }

        return $user->date_of_birth->format('dmy');
    }

    /**
     * Buat kartu untuk user — dipanggil sekali saat user pertama kali
     * membuka halaman kartu. `firstOrCreate` supaya klik ganda atau
     * request bersamaan tidak membuat dua kartu.
     */
    public static function createFor(User $user): self
    {
        $months = (int) config('perpustakaan.library_card.validity_months', 12);

        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'card_number' => self::numberFor($user),
                'valid_until' => now()->addMonthsNoOverflow($months),
            ]
        );
    }

    /**
     * Apakah kartu masih berlaku hari ini?
     */
    public function isValid(): bool
    {
        return $this->valid_until->endOfDay()->isFuture();
    }

    /**
     * Daftar syarat perpanjangan masa berlaku yang BELUM terpenuhi.
     *
     * Return berisi `kunci => kalimat`. Kunci dipakai view untuk label
     * lokal, kalimatnya dipakai pesan flash. Kosong = semua syarat
     * terpenuhi dan kartu boleh diperpanjang.
     *
     * Syarat (keputusan bersama user + PRD):
     *
     * 1. Semua buku sudah dikembalikan — peminjaman aktif
     *    (borrowed/overdue) berarti anggota masih memegang koleksi.
     * 2. Tidak sedang dalam masa sanksi larangan meminjam — sanksi 72 jam
     *    lahir dari keterlambatan, dan anggota yang masih kena sanksi
     *    berarti baru saja tersandung; urusan kartu bisa menunggu sampai
     *    sanksinya selesai (pengganti syarat "tidak ada denda belum lunas"
     *    pada sistem denda uang yang sudah dihapus).
     * 3. Kartu masih berlaku ATAU baru kedaluwarsa maksimal
     *    `renewal_grace_days` hari — lewat itu kartu dianggap lama dan
     *    harus dibuat ulang oleh pustakawan.
     *
     * Aturan sengaja disimpan di SATU method supaya tombol di view,
     * test, dan controller tidak mungkin memakai syarat yang berbeda.
     *
     * @return array<string, string>
     */
    public function renewalBlockers(): array
    {
        $blockers = [];

        if ($this->user->activeLoans()->exists()) {
            $blockers['active_loans'] = 'Masih ada buku yang dipinjam dan belum dikembalikan.';
        }

        if ($this->user->isSuspended()) {
            $blockers['suspended'] = 'Sedang dalam masa sanksi larangan meminjam.';
        }

        if (! $this->isValid()
            && $this->valid_until->lt(now()->subDays(self::renewalGraceDays()))) {
            $blockers['expired_too_long'] = 'Kartu sudah kedaluwarsa lebih dari '.self::renewalGraceDays().' hari.';
        }

        return $blockers;
    }

    /**
     * Semua syarat terpenuhi → kartu boleh diperpanjang?
     */
    public function canRenew(): bool
    {
        return $this->renewalBlockers() === [];
    }

    /**
     * Perpanjang masa berlaku +`renewal_months` (3 bulan) dari HARI INI.
     *
     * Dihitung dari tanggal perpanjangan (bukan menumpuk di ujung masa
     * berlaku lama) supaya anggota tidak bisa mengakumulasi masa berlaku
     * bertahun-tahun hanya dengan menekan tombol berkali-kali.
     *
     * Catatan: kartu BARU berlaku `validity_months` (12 bulan), tapi
     * perpanjangan hanya menambah `renewal_months` (3 bulan) — keduanya
     * sengaja durasi berbeda supaya perpanjangan dilakukan berkala.
     *
     * Pemanggil WAJIB sudah memeriksa `canRenew()` — method ini tidak
     * mengecek ulang supaya logika syarat tetap berada di satu tempat.
     */
    public function renew(): void
    {
        $this->update([
            'valid_until' => now()->addMonthsNoOverflow(self::renewalMonths()),
            // Pengajuan anggota ikut dilaporkan — kartu sudah diperpanjang,
            // tidak ada lagi yang perlu dikonfirmasi pustakawan.
            'renewal_requested_at' => null,
        ]);
    }

    /**
     * Berapa bulan yang ditambahkan oleh SATU kali perpanjangan.
     *
     * Dipakai model, controller (pesan flash + notifikasi), dan view
     * (teks "perpanjangan menambah :months bulan") — satu sumber angka,
     * jadi teks di layar tidak mungkin berbeda dari yang dihitung.
     */
    public static function renewalMonths(): int
    {
        return (int) config('perpustakaan.library_card.renewal_months', 3);
    }

    /**
     * Apakah ada pengajuan perpanjangan yang masih menunggu konfirmasi
     * pustakawan?
     */
    public function hasRenewalRequest(): bool
    {
        return $this->renewal_requested_at !== null;
    }

    /**
     * Berapa hari kartu yang sudah kedaluwarsa masih boleh diperpanjang.
     *
     * Lewat dari ini, anggota harus dibuatkan kartu baru oleh pustakawan
     * (nomornya sama, tapi masa berlaku dihitung ulang dari nol).
     */
    public static function renewalGraceDays(): int
    {
        return (int) config('perpustakaan.library_card.renewal_grace_days', 30);
    }
}
