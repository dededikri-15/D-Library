<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublisherRequest extends FormRequest
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
        $publisherId = $this->route('publisher')?->id;

        return [
            'name' => [
                'required', 'string', 'max:150',
                // Kolomnya unik di database. Tanpa aturan ini, nama kembar lolos
                // validasi lalu meledak jadi QueryException dan HTTP 500.
                // Saat edit, nama penerbit sendiri tetap boleh.
                Rule::unique('publishers', 'name')->ignore($publisherId),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama penerbit',
            'address' => 'alamat',
            'website' => 'situs web',
            'email' => 'email',
            'phone' => 'telepon',
        ];
    }
}
