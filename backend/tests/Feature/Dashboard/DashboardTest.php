<?php

namespace Tests\Feature\Dashboard;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $food;

    private Category $bills;

    private Category $salary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-15 12:00:00');
        $this->user = Sanctum::actingAs(User::factory()->create());
        $this->food = Category::factory()->for($this->user)->expense()->create(['name' => 'Food']);
        $this->bills = Category::factory()->for($this->user)->expense()->create(['name' => 'Bills']);
        $this->salary = Category::factory()->for($this->user)->income()->create(['name' => 'Salary']);
    }

    private function add(Category $category, string $amount, string $date): Transaction
    {
        return Transaction::factory()->inCategory($category)->create(['amount' => $amount, 'transaction_date' => $date]);
    }

    public function test_new_user_gets_zeros_and_empty_arrays(): void
    {
        $response = $this->getJson('/api/dashboard')->assertOk();

        $response
            ->assertJsonPath('data.period', ['key' => 'this_month', 'from' => '2026-09-01', 'to' => '2026-09-30'])
            ->assertJsonPath('data.totals', ['income' => '0.00', 'expenses' => '0.00', 'balance' => '0.00', 'transactions_count' => 0])
            ->assertJsonPath('data.all_time_balance', '0.00')
            ->assertJsonPath('data.expense_by_category', [])
            ->assertJsonPath('data.recent_transactions', [])
            ->assertJsonPath('data.income_vs_expenses.granularity', 'day')
            ->assertJsonCount(30, 'data.income_vs_expenses.points')
            ->assertJsonPath('data.income_vs_expenses.points.0', ['bucket' => '2026-09-01', 'income' => '0.00', 'expenses' => '0.00']);
    }

    public function test_totals_are_exact_decimals(): void
    {
        $this->add($this->salary, '0.10', '2026-09-02');
        $this->add($this->salary, '0.20', '2026-09-03');
        $this->add($this->food, '0.05', '2026-09-04');

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.income', '0.30')
            ->assertJsonPath('data.totals.expenses', '0.05')
            ->assertJsonPath('data.totals.balance', '0.25')
            ->assertJsonPath('data.totals.transactions_count', 3);
    }

    public function test_balance_may_be_negative_and_all_time_covers_every_period(): void
    {
        $this->add($this->salary, '1000.00', '2026-07-01');
        $this->add($this->food, '300.00', '2026-09-05');

        $this->getJson('/api/dashboard')
            ->assertJsonPath('data.totals.balance', '-300.00')
            ->assertJsonPath('data.all_time_balance', '700.00');
    }

    public function test_period_bounds_are_inclusive(): void
    {
        $this->add($this->food, '1.00', '2026-08-31');
        $this->add($this->food, '2.00', '2026-09-01');
        $this->add($this->food, '4.00', '2026-09-30');
        $this->add($this->food, '8.00', '2026-10-01');

        $this->getJson('/api/dashboard?period=this_month')
            ->assertJsonPath('data.totals.expenses', '6.00')
            ->assertJsonPath('data.totals.transactions_count', 2);
    }

    public function test_last_month_excludes_the_current_month(): void
    {
        $this->add($this->food, '1.00', '2026-08-01');
        $this->add($this->food, '2.00', '2026-08-31');
        $this->add($this->food, '4.00', '2026-09-01');

        $this->getJson('/api/dashboard?period=last_month')
            ->assertJsonPath('data.period', ['key' => 'last_month', 'from' => '2026-08-01', 'to' => '2026-08-31'])
            ->assertJsonPath('data.totals.expenses', '3.00');
    }

    public function test_31_day_custom_period_uses_day_granularity(): void
    {
        $this->add($this->food, '5.00', '2026-01-10');

        $response = $this->getJson('/api/dashboard?period=custom&from=2026-01-01&to=2026-01-31')
            ->assertOk()
            ->assertJsonPath('data.income_vs_expenses.granularity', 'day')
            ->assertJsonCount(31, 'data.income_vs_expenses.points');

        $points = collect($response->json('data.income_vs_expenses.points'))->keyBy('bucket');
        $this->assertSame('5.00', $points['2026-01-10']['expenses']);
        $this->assertSame('0.00', $points['2026-01-11']['expenses']);
    }

    public function test_32_day_custom_period_uses_month_granularity_with_zero_filled_buckets(): void
    {
        $this->add($this->food, '5.00', '2026-01-10');
        $this->add($this->salary, '7.50', '2026-03-02');

        $this->getJson('/api/dashboard?period=custom&from=2026-01-01&to=2026-03-15')
            ->assertOk()
            ->assertJsonPath('data.income_vs_expenses.granularity', 'month')
            ->assertJsonPath('data.income_vs_expenses.points', [
                ['bucket' => '2026-01', 'income' => '0.00', 'expenses' => '5.00'],
                ['bucket' => '2026-02', 'income' => '0.00', 'expenses' => '0.00'],
                ['bucket' => '2026-03', 'income' => '7.50', 'expenses' => '0.00'],
            ]);

        $this->getJson('/api/dashboard?period=custom&from=2026-01-01&to=2026-02-01')
            ->assertJsonPath('data.income_vs_expenses.granularity', 'month');
    }

    public function test_custom_period_validation(): void
    {
        $this->getJson('/api/dashboard?period=custom')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['from', 'to']);

        $this->getJson('/api/dashboard?period=custom&from=2026-05-01&to=2026-04-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');

        // 2024 is a leap year: Jan 1 – Dec 31 is exactly 366 days.
        $this->getJson('/api/dashboard?period=custom&from=2024-01-01&to=2024-12-31')->assertOk();
        $this->getJson('/api/dashboard?period=custom&from=2025-01-01&to=2026-01-02')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');

        $this->getJson('/api/dashboard?period=yesterday')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period');
    }

    public function test_expense_breakdown_by_category(): void
    {
        $this->add($this->food, '450.00', '2026-09-02');
        $this->add($this->bills, '750.00', '2026-09-03');
        $this->add($this->salary, '2500.00', '2026-09-01');
        $this->add($this->food, '99.00', '2026-08-15'); // outside the period

        $this->getJson('/api/dashboard')
            ->assertJsonPath('data.expense_by_category', [
                ['category_id' => $this->bills->id, 'name' => 'Bills', 'total' => '750.00', 'percentage' => 62.5],
                ['category_id' => $this->food->id, 'name' => 'Food', 'total' => '450.00', 'percentage' => 37.5],
            ]);
    }

    public function test_recent_transactions_are_the_latest_five_in_the_period(): void
    {
        foreach (range(1, 7) as $day) {
            $this->add($this->food, '1.00', sprintf('2026-09-%02d', $day));
        }

        $this->getJson('/api/dashboard')
            ->assertJsonCount(5, 'data.recent_transactions')
            ->assertJsonPath('data.recent_transactions.0.transaction_date', '2026-09-07')
            ->assertJsonPath('data.recent_transactions.0.amount', '1.00')
            ->assertJsonPath('data.recent_transactions.0.category.name', 'Food');
    }

    public function test_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/dashboard')->assertUnauthorized();
    }
}
