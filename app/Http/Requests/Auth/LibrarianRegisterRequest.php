<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class LibrarianRegisterRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'gender' => ['required', Rule::in(User::genders())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'avatar' => [
                'nullable',
                'image',
                'mimes:'.implode(',', (array) config('perpustakaan.uploads.avatar_mimes')),
                'max:'.(int) config('perpustakaan.uploads.avatar_max_kb'),
                'dimensions:max_width='.config('perpustakaan.uploads.avatar_max_width').',max_height='.config('perpustakaan.uploads.avatar_max_height'),
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
            'avatar.dimensions' => 'Foto profil terlalu besar. Maksimal :width x :height piksel.',
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
            'date_of_birth' => 'tanggal lahir',
            'password' => 'kata sandi',
            'avatar' => 'foto profil',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => Str::lower($this->email)]);
        }
    }
}
