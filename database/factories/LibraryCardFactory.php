<?php

namespace Database\Factories;

use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryCard>
 */
class LibraryCardFactory extends Factory
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
            'card_number' => 'DLP-'.$this->faker->unique()->numberBetween(1, 999999),
            'valid_until' => now()->addYear()->toDateString(),
        ];
    }

    /**
     * Kartu yang sudah kedaluwarsa.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'valid_until' => now()->subDay()->toDateString(),
        ]);
    }
}
