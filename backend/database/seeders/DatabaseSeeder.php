<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a demo account with a few months of activity.
     * Log in as demo@coiny.test / password.
     */
    public function run(): void
    {
        $user = User::factory()->withDefaultCategories()->create([
            'name' => 'Demo User',
            'email' => 'demo@coiny.test',
        ]);

        $categories = $user->categories()->get()->groupBy(fn ($category) => $category->type->value);
        $today = CarbonImmutable::today();

        for ($month = 0; $month < 4; $month++) {
            $start = $today->subMonthsNoOverflow($month)->startOfMonth();
            $end = $month === 0 ? $today : $start->endOfMonth();

            Transaction::factory()
                ->inCategory($categories['income']->firstWhere('name', 'Salary'))
                ->create(['amount' => 2500, 'transaction_date' => $start->toDateString(), 'description' => 'Monthly salary']);

            foreach (range(1, 12) as $i) {
                Transaction::factory()
                    ->inCategory($categories['expense']->random())
                    ->create(['transaction_date' => fake()->dateTimeBetween($start, $end)->format('Y-m-d')]);
            }

            Transaction::factory()
                ->inCategory($categories['income']->where('name', '!=', 'Salary')->random())
                ->create(['amount' => fake()->randomFloat(2, 50, 600), 'transaction_date' => fake()->dateTimeBetween($start, $end)->format('Y-m-d')]);
        }
    }
}
