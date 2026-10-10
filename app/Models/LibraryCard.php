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
}
