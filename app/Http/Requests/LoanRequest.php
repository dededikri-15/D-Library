<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'borrowed_at' => [
                'required',
                'date',
                'before_or_equal:'.now(config('perpustakaan.display_timezone'))->toDateString(),
            ],
            // Tanggal jatuh tempo dihitung otomatis oleh sistem, jadi tidak
            // dikirim dari form. Status juga dihitung, bukan dipilih manual.
            'status' => ['sometimes', Rule::in(Loan::statuses())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'anggota',
            'book_id' => 'buku',
            'borrowed_at' => 'tanggal pinjam',
        ];
    }
}
