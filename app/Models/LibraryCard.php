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
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nomor kartu untuk user tertentu.
     *
     * Format: TAHUN-URUTAN (contoh: 2026-0008). Deterministik — satu user
     * selalu menghasilkan nomor yang sama. Tahun di depan membuat nomor
     * terasa seperti kartu keanggotaan sungguhan, bukan sekadar ID database.
     */
    public static function numberFor(User $user): string
    {
        return now()->year.'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
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
}
