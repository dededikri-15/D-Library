<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Kunci rate limit eindeun untuk kombinasi email + IP, supaya satu penyerang
     * tidak bisa mengunci seluruh akun orang lain hanya dengan menebak email.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'email',
            'password' => 'kata sandi',
        ];
    }

    /**
     * Email disimpan lowercase (`UserRequest`idan `RegisterRequest` sama-sama
     * menormalkannya), sedangkan perbandingan teks di PostgreSQL bersifat
     * case-sensitive. Jadi "Anggota@Contoh.test" akan gagal mencari akun yang
     * email-nya "anggota@contoh.test" — dan pengguna akan mengira kata
     * sandinya yang salah. Karena itu input dinormalkan di sini, bukan
     * ditolak, karena keyboard ponsel sering mengetik huruf besar sendiri.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => Str::lower($this->email)]);
        }
    }

    /**
     * Coba autentikasi kredensial, dengan pembatasan percobaan gagal.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang dimasukkan salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) config('perpustakaan.security.login_max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }
}
