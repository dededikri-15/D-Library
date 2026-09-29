<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_PUSTAKAWAN = 'pustakawan';

    public const ROLE_ANGGOTA = 'anggota';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function roles(): array
    {
        return [
            self::ROLE_PUSTAKAWAN,
            self::ROLE_ANGGOTA,
        ];
    }

    /**
     * Label role dalam Bahasa Indonesia, untuk ditampilkan di UI.
     *
     * @var array<string, string>
     */
    public const ROLE_LABELS = [
        self::ROLE_PUSTAKAWAN => 'Pustakawan',
        self::ROLE_ANGGOTA => 'Anggota',
    ];

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? Str::ucfirst((string) $this->role);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isPustakawan(): bool
    {
        return $this->hasRole(self::ROLE_PUSTAKAWAN);
    }

    public function isAnggota(): bool
    {
        return $this->hasRole(self::ROLE_ANGGOTA);
    }

    /**
     * Alias Bahasa Indonesia dari isAnggota(), supaya Blade bisa menulis
     * isMember() dan tidak perlu remember istilah "anggota".
     */
    public function isMember(): bool
    {
        return $this->isAnggota();
    }

    public function isStaff(): bool
    {
        return $this->isPustakawan();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->loans()->active();
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
