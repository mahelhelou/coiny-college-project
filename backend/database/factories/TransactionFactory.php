<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Owner, category and type are kept consistent (BR-02, BR-03).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => TransactionType::Expense,
            'category_id' => fn (array $attributes) => Category::factory()->create([
                'user_id' => $attributes['user_id'],
                'type' => $attributes['type'],
            ])->id,
            'amount' => fake()->randomFloat(2, 1, 500),
            'transaction_date' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'description' => fake()->optional()->sentence(4),
        ];
    }

    /**
     * Attach to an existing category, inheriting its owner and type.
     */
    public function inCategory(Category $category): static
    {
        return $this->state([
            'user_id' => $category->user_id,
            'category_id' => $category->id,
            'type' => $category->type,
        ]);
    }
}
