<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingHistory>
 */
class ReadingHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory(),
            'last_page' => fake()->numberBetween(1, 100),
            'last_read_at' => now()->subDays(fake()->numberBetween(0, 14)),
        ];
    }
}
