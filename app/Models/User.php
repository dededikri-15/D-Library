<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
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
        'date_of_birth',
        'avatar',
        'suspended_until',
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
            'date_of_birth' => 'date',
            'suspended_until' => 'datetime',
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

    /**
     * Apakah user sudah mengisi tanggal lahir?
     *
     * Dipakai untuk menentukan apakah nomor kartu perpustakaan bisa
     * dibuat (format DDMMYY membutuhkan tanggal lahir).
     */
    public function hasBirthDate(): bool
    {
        return $this->date_of_birth !== null;
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class)->latest('created_at');
    }

    public function lastLoginAt(): ?Carbon
    {
        return $this->activities()
            ->where('type', UserActivity::TYPE_LOGIN)
            ->value('created_at');
    }

    public function lastLogoutAt(): ?Carbon
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

    /*
    |------------------------------------------------------------------
    | Sanksi keterlambatan (pengganti denda uang)
    |------------------------------------------------------------------
    |
    | Satu kolom `suspended_until` menjalankan dua pekerjaan sekaligus:
    | menandai bahwa sanksi pernah dijatuhkan, DAN menyimpan batas akhirnya.
    | Sanksi dianggap berlaku selama timestamp masih di masa depan —
    | jadi "otomatis aktif kembali setelah 72 jam" tidak butuh scheduler:
    | waktu yang bekerja, bukan job.
    |
    */

    /**
     * Apakah anggota ini sedang dalam masa sanksi larangan meminjam?
     */
    public function isSuspended(): bool
    {
        return $this->suspended_until !== null && now()->lessThan($this->suspended_until);
    }

    /**
     * Jatuh tempo sanksi (batas akhir larangan meminjam).
     *
     * Nilai tetap dikembalikan walaupun sanksi sudah lewat, supaya pustakawan
     * bisa melihat riwayat "sanksi berakhir kapan" di halaman pengguna.
     */
    public function suspensionEndsAt(): ?Carbon
    {
        return $this->suspended_until;
    }

    /**
     * Terapkan sanksi larangan meminjam selama `suspension_hours` (72 jam)
     * sejak SEKARANG — dipanggil saat pustakawan memproses pengembalian
     * buku yang terlambat.
     *
     * Sanksi HANYA diperpanjang, tidak pernah dipendekkan: kalau anggota
     * baru saja kena sanksi dari pengembalian lain yang diproses lebih
     * awal, memproses buku kedua tidak boleh menggeser batas akhir ke
     * waktu yang lebih dekat — itu namanya memberi remisi diam-diam.
     */
    public function applyLateReturnSuspension(): void
    {
        $until = now()->addHours((int) config('perpustakaan.loan.suspension_hours', 72));

        if ($this->suspended_until !== null && $this->suspended_until->greaterThan($until)) {
            return;
        }

        $this->update(['suspended_until' => $until]);
    }

    /**
     * Label tampilan masa sanksi, siap dicetak ke view.
     *
     * @return array{active: bool, until: Carbon}
     */
    public function suspensionStatus(): array
    {
        return [
            'active' => $this->isSuspended(),
            'until' => $this->suspended_until
                ?? now()->addHours((int) config('perpustakaan.loan.suspension_hours', 72)),
        ];
    }

    /**
     * Seluruh akun pustakawan.
     *
     * Dipakai pemberitahuan yang harus diterima MEJA SIRKULASI (peminjaman
     * baru, pengajuan pengembalian, keterlambatan). Menulis
     * `where('role', ...)` berulang di tiap aksi akan membuat role jadi
     * pengetahuan yang tersebar — kalau suatu saat ada role tambahan yang
     * ikut bertugas, cukup satu tempat ini yang diubah.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopePustakawan(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_PUSTAKAWAN);
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

    public function libraryCard(): HasOne
    {
        return $this->hasOne(LibraryCard::class);
    }
}
