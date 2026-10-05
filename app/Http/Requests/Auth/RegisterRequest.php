<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('perpustakaan.registration.enabled', true);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'gender' => ['required', Rule::in(User::genders())],
            'password' => ['required', 'confirmed', Password::defaults()],

            /*
             * Foto profil saat pendaftaran sifatnya OPSIONAL, dan itu keputusan
             * sadar, bukan sekadar lupa 'required'.
             *
             * Batas pendaftaran publik sudah ditetapkan agar orang bisa cepat
             * selesai mendaftar; memaksa unggah foto di sini membuat sebagian
             * orang membatalkan pendaftaran, dan harga yang dibayar tidak sebanding.
             * Tanpa foto, user tetap punya akun penuh dan bisa menambahkannya
             * kapan saja lewat /profil.
             */
            'avatar' => [
                'nullable',
                'image',
                'mimes:'.implode(',', (array) config('perpustakaan.uploads.avatar_mimes')),
                'max:'.(int) config('perpustakaan.uploads.avatar_max_kb'),
            ],
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
     * Email dinormalkan ke huruf kecil, bukan ditolak kalau ada huruf besar:
     * keyboard ponsel sering mengetik huruf besar sendiri, dan `users.email`
     * punya unique constraint sehingga bentuk email harus seragam.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => Str::lower($this->email)]);
        }
    }
}
