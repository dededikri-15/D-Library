<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $borrowedAt = now()->subDays(fake()->numberBetween(0, 5));

        return [
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory(),
            'borrowed_at' => $borrowedAt,
            'due_at' => (clone $borrowedAt)->addDays(config('perpustakaan.loan.duration_days')),
            'returned_at' => null,
            'status' => Loan::STATUS_BORROWED,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'returned_at' => now(),
            'status' => Loan::STATUS_RETURNED,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Loan::STATUS_OVERDUE,
            'due_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
