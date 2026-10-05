<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_PUSTAKAWAN = 'pustakawan';

    public const ROLE_ANGGOTA = 'anggota';

    public const GENDER_LAKI_LAKI = 'laki-laki';

    public const GENDER_PEREMPUAN = 'perempuan';

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
        'gender',
        'avatar',
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

    /** @return list<string> */
    public static function genders(): array
    {
        return [
            self::GENDER_LAKI_LAKI,
            self::GENDER_PEREMPUAN,
        ];
    }

    /** @return array<string, string> */
    public static function genderOptions(): array
    {
        return [
            self::GENDER_LAKI_LAKI => __('users.gender_male'),
            self::GENDER_PEREMPUAN => __('users.gender_female'),
        ];
    }

    public function genderLabel(): ?string
    {
        return match ($this->gender) {
            self::GENDER_LAKI_LAKI => __('users.gender_male'),
            self::GENDER_PEREMPUAN => __('users.gender_female'),
            default => null,
        };
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class)->latest('created_at');
    }

    public function lastLoginAt(): ?\Illuminate\Support\Carbon
    {
        return $this->activities()
            ->where('type', UserActivity::TYPE_LOGIN)
            ->value('created_at');
    }

    public function lastLogoutAt(): ?\Illuminate\Support\Carbon
    {
        return $this->activities()
            ->where('type', UserActivity::TYPE_LOGOUT)
            ->value('created_at');
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
        return match ($this->role) {
            self::ROLE_PUSTAKAWAN => __('roles.pustakawan'),
            self::ROLE_ANGGOTA => __('roles.anggota'),
            default => Str::ucfirst((string) $this->role),
        };
    }

    /**
     * Disk tempat foto profil disimpan.
     *
     * Sama seperti cover buku, avatar memakai disk publik: file-nya hanya
     * gambar profil, bukan dokumen, dan tidak ada di URL yang perlu ditebak.
     */
    public static function avatarDisk(): string
    {
        return (string) config('perpustakaan.uploads.avatar_disk');
    }

    /**
     * URL foto profil, atau null kalau user belum mengunggah foto.
     *
     * Sengaja null (bukan string kosong): komponen memakai ilustrasi gender,
     * dan string kosong justru membuat `<img src="">` memuat ulang halaman.
     */
    public function avatarUrl(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        return Storage::disk(static::avatarDisk())->url($this->avatar);
    }

    /**
     * Inisial untuk avatar huruf, dipakai kalau user tidak punya foto.
     *
     * Hanya huruf pertama yang diambil. Nama Indonesia sering dua kata
     * ("Budi Santoso") dan dua inisial butuh ruang lebih pada lingkaran kecil
     * di navbar, sehingga satu huruf selalu muat tanpa terpotong.
     */
    public function initials(): string
    {
        $name = trim((string) $this->name);

        if ($name === '') {
            return '?';
        }

        return Str::upper(Str::substr($name, 0, 1));
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
