<?php

namespace App\Models;

use Database\Factories\ReadingHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    /** @use HasFactory<ReadingHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'last_page',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_page' => 'integer',
            'last_read_at' => 'datetime',
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
