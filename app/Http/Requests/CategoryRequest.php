<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                // Sama seperti slug, kolomnya unik di database. Tanpa aturan
                // ini nama kategori kembar lolos validasi lalu meledak jadi
                // QueryException dan HTTP 500.
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'slug' => [
                'nullable', 'string', 'max:100', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'slug' => 'slug',
            'description' => 'deskripsi',
        ];
    }

    /**
     * Slug dibuat otomatis dari nama bila tidak dikirim manual.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && is_string($this->input('name'))) {
            $this->merge(['slug' => Category::slugFor($this->input('name'))]);
        }
    }
}
