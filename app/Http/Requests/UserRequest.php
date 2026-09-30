<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPustakawan() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            // Saat edit, kata sandi boleh dikosongkan (artinya tidak diubah).
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::roles())],
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
            'password' => 'kata sandi',
            'role' => 'role',
        ];
    }

    /**
     * Email dinormalkan ke huruf kecil, bukan ditolak kalau ada huruf besar.
     *
     * Alasannya dua. Pertama, `users.email` punya unique constraint, jadi dua
     * akun dengan email yang hanya berbeda huruf besar akan lolos validasi lalu
     * meledak jadi QueryException. Kedua, seluruh tempat lain membandingkan
     * email secara case-sensitive, jadi email yang tersimpan harus selalu
     * dalam satu bentuk.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => Str::lower($this->email)]);
        }
    }
}
