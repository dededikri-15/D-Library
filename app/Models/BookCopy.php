<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BookCopy extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'book_id',
        'inventory_code',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $copy): void {
            $copy->inventory_code ??= 'EX-'.Str::upper(Str::random(12));
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Tersedia',
            self::STATUS_BORROWED => 'Dipinjam',
            self::STATUS_INACTIVE => 'Tidak aktif',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
