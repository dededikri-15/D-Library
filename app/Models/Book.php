<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'title',
        'isbn',
        'description',
        'publication_year',
        'pages',
        'cover',
        'file',
        'category_id',
        'author_id',
        'publisher_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'pages' => 'integer',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_BORROWED,
            self::STATUS_INACTIVE,
        ];
    }

    /**
     * Label Indonesia untuk dropdown form. Nilai teknisnya sengaja tidak
     * ditampilkan supaya pustakawan tidak perlu menghafal string database.
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Tersedia',
            self::STATUS_BORROWED => 'Dipinjam',
            self::STATUS_INACTIVE => 'Tidak aktif',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function hasCover(): bool
    {
        return filled($this->cover);
    }

    public function hasFile(): bool
    {
        return filled($this->file);
    }

    /**
     * Siapa yang boleh membaca file PDF buku ini.
     *
     * Hanya pustakawan dan anggota yang sedang meminjam
     * buku dengan status `borrowed`. Loan yang sudah dikembalikan atau sudah
     * ditandai `overdue` tidak lagi diberi akses baca.
     */
    public function canBeReadBy(?User $user): bool
    {
        if (! $user || ! $this->hasFile()) {
            return false;
        }

        if ($user->isStaff()) {
            return true;
        }

        return $this->loans()
            ->where('user_id', $user->id)
            ->where('status', Loan::STATUS_BORROWED)
            ->exists();
    }

    /**
     * Konfigurasi disk tempat cover & file PDF disimpan.
     * Cover publik, PDF privat (lihat config/perpustakaan.php).
     */
    public static function coverDisk(): string
    {
        return (string) config('perpustakaan.uploads.cover_disk');
    }

    public static function bookFileDisk(): string
    {
        return (string) config('perpustakaan.uploads.book_file_disk');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class);
    }
}
