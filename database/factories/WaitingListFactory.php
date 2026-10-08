<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use App\Models\WaitingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitingList>
 */
class WaitingListFactory extends Factory
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
            'notified_at' => null,
        ];
    }

    /**
     * Entri yang sudah dikirimi notifikasi "buku tersedia".
     */
    public function notified(): static
    {
        return $this->state(fn () => ['notified_at' => now()]);
    }
}
