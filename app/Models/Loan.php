<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'user_id',
        'book_id',
        'borrowed_at',
        'due_at',
        'returned_at',
        'return_requested_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'return_requested_at' => 'datetime',
        ];
    }

    /**
     * Isi tanggal pinjam dan jatuh tempo kalau pemanggil tidak memberi.
     *
     * `due_at` dan `borrowed_at` kolomnya NOT NULL, jadi `Loan::create()` yang
     * hanya mengisi `book_id`, `user_id`, dan `status` gagal dengan
     * QueryException yang kurang jelas — akibatnya pemanggil (termasuk factory
     * dan skrip perbaikan data) harus mengingat aturan yang sama. Hook ini
     * membuat modelnya sendiri jadi lengkap, dan nilai yang memang sudah
     * diisi tidak ditimpa.
     */
    protected static function booted(): void
    {
        static::creating(function (self $loan): void {
            $loan->borrowed_at ??= now();

            // `dueAt()` adalah sumber kebenaran aturan durasi, jadi DEFAULT
            // juga ikut memakainya — bukan meniru perhitungannya di sini.
            $loan->due_at ??= self::dueAt($loan->borrowed_at);
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_BORROWED,
            self::STATUS_RETURNED,
            self::STATUS_OVERDUE,
        ];
    }

    /**
     * Label bahasa Indonesia untuk setiap status, siap dipakai `<select>`.
     *
     * Nilai di database sengaja bahasa Inggris (`borrowed`) supaya migration
     * dan constraint-nya tidak bergantung pada bahasa. Tapi nilai itu tidak
     * boleh sampai tampil ke pengguna — dropdown filter yang isinya
     * "borrowed / returned / overdue" terlihat seperti bug, bukan fitur.
     *
     * Dipakai juga oleh `x-status-badge`, jadi label di dropdown dan label di
     * badge tidak mungkin berbeda.
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_BORROWED => 'Dipinjam',
            self::STATUS_RETURNED => 'Dikembalikan',
            self::STATUS_OVERDUE => 'Terlambat',
        ];
    }

    /**
     * Tanggal jatuh tempo dihitung dari tanggal pinjam.
     *
     * Sengaja method statis di model, bukan ditulis inline di controller:
     * config/perpustakaan.php menyebut nama method ini, jadi aturan "berapa
     * lama buku boleh dipinjam" hanya punya satu implementasi. Kalau nanti
     * aturan berubah (mis. jadi berbeda untuk buku digital), cukup ubah di
     * sini.
     */
    public static function dueAt(Carbon $borrowedAt): Carbon
    {
        return $borrowedAt->copy()->addDays((int) config('perpustakaan.loan.duration_days'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_BORROWED, self::STATUS_OVERDUE]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_BORROWED, self::STATUS_OVERDUE], true);
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function hasReturnRequest(): bool
    {
        return $this->return_requested_at !== null;
    }

    public function displayDate(?Carbon $date): ?Carbon
    {
        return $date?->copy()->setTimezone((string) config('perpustakaan.display_timezone', 'Asia/Jakarta'));
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
