<?php

namespace App\Models;

use Database\Factories\WaitingListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu anggota sedang mengantre satu buku.
 *
 * Entri dibuat hanya selama buku TIDAK tersedia (lihat
 * WaitingListController::store), dan dihapus ketika pemiliknya meminjam
 * buku itu, membatalkan sendiri, kedaluwarsa, atau bukunya dihapus.
 */
class WaitingList extends Model
{
    /** @use HasFactory<WaitingListFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
