<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Formulir edit profil milik user yang sedang login.
 *
 * Bedanya dengan `UserRequest` (yang dipakai pustakawan untuk mengelola akun
 * orang lain) ada dua hal yang disengaja:
 *
 * 1. Tidak ada field `role`. User boleh mengubah namanya sendiri, tapi tidak
 *    boleh mengubah role-nya sendiri — kalau boleh, dia bisa menaikkan dirinya
 *    jadi pustakawan. Role hanya bisa diubah pustakawan lewat `UserRequest`.
 * 2. `authorize()` hanya memeriksa "sudah login", bukan "adalah pustakawan".
 *    Halaman profil harusnya terbuka untuk anggota juga.
 */
class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                // `ignore($this->user()->id)` wajib: tanpa itu, user yang tidak
                // mengubah email-nya pun akan bentrok dengan barisnya sendiri.
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'gender' => ['required', Rule::in(User::genders())],
            // Dikosongkan = kata sandi lama dipertahankan.
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'avatar' => [
                'nullable',
                'image',
                'mimes:'.implode(',', (array) config('perpustakaan.uploads.avatar_mimes')),
                'max:'.(int) config('perpustakaan.uploads.avatar_max_kb'),
            ],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.image' => 'Foto profil harus berupa gambar.',
            'avatar.mimes' => 'Foto profil harus berformat JPG, PNG, atau WebP.',
            'avatar.max' => 'Ukuran foto profil maksimal :max kilobyte.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'gender' => 'jenis kelamin',
            'password' => 'kata sandi',
            'avatar' => 'foto profil',
        ];
    }

    /**
     * Email dinormalkan ke huruf kecil, sama seperti `RegisterRequest` dan
     * `UserRequest`. Alasannya sama: `users.email` punya unique constraint, dan
     * seluruh query membandingkan email secara case-sensitive.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => Str::lower($this->email)]);
        }
    }
}
