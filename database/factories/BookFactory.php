<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->unique()->sentence(4), '.'),
            'isbn' => fake()->unique()->numerify('978-###-###-###-#'),
            'description' => fake()->paragraphs(2, true),
            'publication_year' => fake()->numberBetween(1980, 2026),
            'pages' => fake()->numberBetween(50, 900),
            'cover' => null,
            'file' => null,
            'category_id' => Category::factory(),
            'author_id' => Author::factory(),
            'publisher_id' => Publisher::factory(),
            'status' => Book::STATUS_AVAILABLE,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Book::STATUS_AVAILABLE,
        ]);
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Book::STATUS_BORROWED,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Book::STATUS_INACTIVE,
        ]);
    }
}
