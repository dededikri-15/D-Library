<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * Ubah nama kategori menjadi slug yang siap disimpan.
     *
     * `Str::slug()` hanya bisa memangkas huruf Latin, angka, dan tanda hubung.
     * Nama yang seluruhnya non-Latin (mis. '文学') menghasilkan string kosong,
     * dan karena `categories.slug` punya unique constraint, kategori kedua
     * seperti itu akan menabrak constraint tersebut lalu meledak jadi
     * QueryException (HTTP 500). Karena itu slug kosong dicadangkan dengan
     * awalan acak yang tetap terbaca.
     */
    public static function slugFor(string $name): string
    {
        return Str::slug($name) ?: 'kategori-'.Str::lower(Str::random(6));
    }
}
