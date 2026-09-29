<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Book::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $bookId = $this->route('book')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required', 'string', 'max:20',
                // Unik per buku, tapi saat edit ISBN miliknya sendiri tetap boleh.
                Rule::unique('books', 'isbn')->ignore($bookId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'publication_year' => [
                'required', 'integer',
                'min:1000',
                // Tidak boleh melebihi tahun berjalan.
                'max:'.(int) now()->year,
            ],
            'pages' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::in(Book::statuses())],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'author_id' => ['required', 'integer', 'exists:authors,id'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],

            /*
             * Upload bersifat opsional: buku boleh disimpan tanpa cover maupun
             * tanpa file PDF. Kalau ada, keduanya wajib lolos pemeriksaan tipe
             * dan ukuran. Nama file asli dari user tidak pernah dipakai —
             * controller selalu membuat nama baru.
             */
            'cover' => [
                'nullable',
                'image',
                'mimes:'.implode(',', (array) config('perpustakaan.uploads.cover_mimes')),
                'max:'.(int) config('perpustakaan.uploads.cover_max_kb'),
            ],
            'file' => [
                'nullable',
                'mimes:pdf',
                // Periksa MIME asli, bukan hanya ekstensi: file bernama
                // "buku.pdf" yang isinya PHP harus tetap ditolak.
                'mimetypes:application/pdf',
                'max:'.(int) config('perpustakaan.uploads.book_file_max_kb'),
            ],
            'remove_cover' => ['nullable', 'boolean'],
            'remove_file' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'publication_year.max' => 'Tahun terbit tidak boleh melebihi tahun berjalan.',
            'isbn.unique' => 'ISBN ini sudah dipakai buku lain.',
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.mimes' => 'Cover harus berformat JPG, PNG, atau WebP.',
            'cover.max' => 'Ukuran cover maksimal :max kilobyte.',
            'file.mimes' => 'File buku harus berformat PDF.',
            'file.mimetypes' => 'File buku harus benar-benar berformat PDF.',
            'file.max' => 'Ukuran file buku maksimal :max kilobyte.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'isbn' => 'ISBN',
            'publication_year' => 'tahun terbit',
            'pages' => 'jumlah halaman',
            'category_id' => 'kategori',
            'author_id' => 'penulis',
            'publisher_id' => 'penerbit',
            'cover' => 'cover',
            'file' => 'file buku',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->isbn)) {
            $this->merge(['isbn' => trim($this->isbn)]);
        }
    }
}
