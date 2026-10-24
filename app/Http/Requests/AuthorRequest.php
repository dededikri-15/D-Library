<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'biography' => ['nullable', 'string', 'max:2000'],
            'photo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', (array) config('perpustakaan.uploads.cover_mimes')),
                'max:'.(int) config('perpustakaan.uploads.cover_max_kb'),
                'dimensions:max_width='.config('perpustakaan.uploads.cover_max_width').',max_height='.config('perpustakaan.uploads.cover_max_height'),
            ],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.image' => 'Foto harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal :max kilobyte.',
            'photo.dimensions' => 'Foto terlalu besar. Maksimal :width x :height piksel.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama penulis',
            'biography' => 'biografi',
            'photo' => 'foto',
        ];
    }
}
