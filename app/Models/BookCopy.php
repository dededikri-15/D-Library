<?php

namespace App\Models;

use App\Support\InventoryCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            $copy->inventory_code ??= InventoryCode::generate($copy->book ?? Book::query()->findOrFail($copy->book_id));
        });
    }

    /**
     * Susun ulang kode inventaris seluruh eksemplar sebuah buku.
     *
     * Dipanggil dari Book::updated() ketika judul, kategori, atau penulis
     * berubah — DDC dan penanda penulis ikut berubah, jadi kode lama tidak
     * lagi mencerminkan isi buku. Nomor urut tiap eksemplar dipertahankan
     * berdasarkan urutan id, supaya eksemplar yang sedang dipinjam tidak
     * berganti nomor secara acak.
     */
    public static function refreshCodesFor(Book $book): void
    {
        $prefix = InventoryCode::prefixFor($book);

        $book->copies()
            ->orderBy('id')
            ->get()
            ->each(function (self $copy, int $index) use ($prefix): void {
                $code = $prefix.sprintf('%02d', $index + 1);

                if ($copy->inventory_code !== $code) {
                    // saveQuietly: tidak memicu event lagi, dan tidak menghitung
                    // total_copies ulang — yang berubah hanya kodenya.
                    $copy->inventory_code = $code;
                    $copy->saveQuietly();
                }
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
