<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibrarianRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya aktif ketika konfigurasi perpustakaan mengizinkan registrasi
        // dan hanya bisa diakses jika belum ada akun pustakawan sama sekali
        $registrationEnabled = config('perpustakaan.registration.enabled', true);

        if (! $registrationEnabled) {
            return false;
        }

        // Cek apakah sudah ada akun pustakawan sama sekali
        $hasLibrarian = User::role(User::ROLE_PUSTAKAWAN)->exists();

        if ($hasLibrarian) {
            // Sudah ada pustakawan, hanya bisa diakses jika ada secret key yang valid
            // (untah fitur ini digunakan saat setup awal)
            return false;
        }

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id ?? null),
            ],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
            'secret' => [
                'required',
                // Validasi secret key harus sesuai dengan konfigurasi
                function ($attr, $value, $fail) {
                    $secret = config('perpustakaan.registration.librarian_registration.secret');

                    if (! $secret) {
                        // Jika tidak ada secret yang dikonfigurasi, tetap validasi
                        // bahwa form ini hanya bisa diakses saat belum ada pustakawan
                        $fail('Registrasi pustakawan saat ini tidak aktif. Hubungi administrator.');
                    } elseif ($value !== $secret) {
                        $fail('Kode rahasia tidak sesuai.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
            'password.min' => 'Kata sandi minimal harus memiliki :min karakter.',
            'secret.required' => 'Kode rahasia wajib diisi.',
            'secret' => 'Kode rahasia tidak sesuai.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'password' => 'kata sandi',
            'secret' => 'kode rahasia',
        ];
    }
}