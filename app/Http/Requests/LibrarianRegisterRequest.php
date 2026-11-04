<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class LibrarianRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! config('perpustakaan.registration.librarian_registration.enabled', false)
            || blank(config('perpustakaan.registration.librarian_registration.secret'))) {
            return false;
        }

        return ! User::pustakawan()->exists();
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
                Rule::unique(User::class),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'secret' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $secret = (string) config('perpustakaan.registration.librarian_registration.secret');

                    if ($secret === '' || ! hash_equals($secret, (string) $value)) {
                        $fail(__('auth.invalid_librarian_secret'));
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

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => Str::lower($this->email)]);
        }
    }
}
