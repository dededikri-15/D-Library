<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReadingHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Riwayat baca adalah data pribadi, jadi hanya boleh diubah pemiliknya.
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'last_page' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'book_id' => 'buku',
            'last_page' => 'halaman terakhir',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'last_page.min' => 'Halaman terakhir minimal 1.',
        ];
    }
}
