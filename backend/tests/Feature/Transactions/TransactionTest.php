<?php

namespace Tests\Feature\Transactions;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $food;

    private Category $salary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-15 12:00:00');
        $this->user = Sanctum::actingAs(User::factory()->create());
        $this->food = Category::factory()->for($this->user)->expense()->create(['name' => 'Food']);
        $this->salary = Category::factory()->for($this->user)->income()->create(['name' => 'Salary']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'expense',
            'amount' => '12.50',
            'category_id' => $this->food->id,
            'transaction_date' => '2026-09-10',
            'description' => 'Lunch',
        ], $overrides);
    }

    public function test_creates_a_transaction(): void
    {
        $this->postJson('/api/transactions', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.amount', '12.50')
            ->assertJsonPath('data.transaction_date', '2026-09-10')
            ->assertJsonPath('data.description', 'Lunch')
            ->assertJsonPath('data.category.name', 'Food');

        $this->assertDatabaseHas('transactions', ['user_id' => $this->user->id, 'amount' => '12.50']);
    }

    public function test_user_id_in_body_is_ignored(): void
    {
        $other = User::factory()->create();

        $this->postJson('/api/transactions', $this->payload(['user_id' => $other->id]))->assertCreated();

        $this->assertSame(0, $other->transactions()->count());
        $this->assertSame(1, $this->user->transactions()->count());
    }

    public function test_numeric_amount_is_returned_as_two_decimal_string(): void
    {
        $this->postJson('/api/transactions', $this->payload(['amount' => 250]))
            ->assertCreated()
            ->assertJsonPath('data.amount', '250.00');
    }

    public function test_blank_description_is_stored_as_null(): void
    {
        $this->postJson('/api/transactions', $this->payload(['description' => '   ']))
            ->assertCreated()
            ->assertJsonPath('data.description', null);
    }

    public static function invalidAmounts(): array
    {
        return [
            'zero' => [0],
            'negative' => [-5],
            'three decimals' => ['1.234'],
            'non-numeric' => ['abc'],
            'above max' => ['10000000000.00'],
            'missing' => [null],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_rejects_invalid_amounts(mixed $amount): void
    {
        $this->postJson('/api/transactions', $this->payload(['amount' => $amount]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_accepts_the_maximum_amount(): void
    {
        $this->postJson('/api/transactions', $this->payload(['amount' => '9999999999.99']))
            ->assertCreated()
            ->assertJsonPath('data.amount', '9999999999.99');
    }

    public function test_category_of_the_wrong_type_is_rejected(): void
    {
        $this->postJson('/api/transactions', $this->payload(['type' => 'income', 'category_id' => $this->food->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public static function invalidDates(): array
    {
        return [
            'after tomorrow' => ['2026-09-17'],
            'before 2000' => ['1999-12-31'],
            'wrong format' => ['10/09/2026'],
            'not a date' => ['2026-02-30'],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_rejects_invalid_dates(string $date): void
    {
        $this->postJson('/api/transactions', $this->payload(['transaction_date' => $date]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('transaction_date');
    }

    public function test_accepts_tomorrow_and_2000_01_01(): void
    {
        $this->postJson('/api/transactions', $this->payload(['transaction_date' => '2026-09-16']))->assertCreated();
        $this->postJson('/api/transactions', $this->payload(['transaction_date' => '2000-01-01']))->assertCreated();
    }

    public function test_description_max_length(): void
    {
        $this->postJson('/api/transactions', $this->payload(['description' => str_repeat('a', 256)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('description');
    }

    public function test_shows_a_transaction(): void
    {
        $transaction = Transaction::factory()->inCategory($this->food)->create(['amount' => '9.90']);

        $this->getJson("/api/transactions/{$transaction->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.amount', '9.90');
    }

    public function test_updates_a_transaction(): void
    {
        $transaction = Transaction::factory()->inCategory($this->food)->create();

        $this->putJson("/api/transactions/{$transaction->id}", $this->payload([
            'type' => 'income',
            'category_id' => $this->salary->id,
            'amount' => '1000',
            'description' => null,
        ]))
            ->assertOk()
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.amount', '1000.00')
            ->assertJsonPath('data.category.id', $this->salary->id)
            ->assertJsonPath('data.description', null);
    }

    public function test_update_validates_like_create(): void
    {
        $transaction = Transaction::factory()->inCategory($this->food)->create();

        $this->putJson("/api/transactions/{$transaction->id}", $this->payload(['type' => 'income']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_deletes_a_transaction(): void
    {
        $transaction = Transaction::factory()->inCategory($this->food)->create();

        $this->deleteJson("/api/transactions/{$transaction->id}")->assertNoContent();

        $this->assertModelMissing($transaction);
    }

    public function test_missing_transaction_returns_404_without_leaking_details(): void
    {
        $this->getJson('/api/transactions/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not found.']);
    }

    public function test_list_is_paginated_15_per_page_newest_first(): void
    {
        Transaction::factory()->count(20)->inCategory($this->food)->create(['transaction_date' => '2026-09-01']);
        $newest = Transaction::factory()->inCategory($this->food)->create(['transaction_date' => '2026-09-12']);

        $this->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_same_day_transactions_are_ordered_by_id_desc(): void
    {
        $first = Transaction::factory()->inCategory($this->food)->create(['transaction_date' => '2026-09-01']);
        $second = Transaction::factory()->inCategory($this->food)->create(['transaction_date' => '2026-09-01']);

        $this->getJson('/api/transactions')
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id);
    }

    public function test_per_page_is_capped_at_100(): void
    {
        $this->getJson('/api/transactions?per_page=100')->assertOk();
        $this->getJson('/api/transactions?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_filters_combine_with_and(): void
    {
        $match = Transaction::factory()->inCategory($this->food)->create([
            'transaction_date' => '2026-09-05', 'description' => 'Pizza night',
        ]);
        Transaction::factory()->inCategory($this->food)->create(['transaction_date' => '2026-08-05', 'description' => 'Pizza']);
        Transaction::factory()->inCategory($this->food)->create(['transaction_date' => '2026-09-06', 'description' => 'Taxi']);
        Transaction::factory()->inCategory($this->salary)->create(['transaction_date' => '2026-09-05', 'description' => 'Pizza bonus']);

        $query = http_build_query([
            'type' => 'expense',
            'category_id' => $this->food->id,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-05',
            'search' => 'pizza',
        ]);

        $this->getJson("/api/transactions?{$query}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_treats_wildcards_literally(): void
    {
        Transaction::factory()->inCategory($this->food)->create(['description' => 'Anything']);

        $this->getJson('/api/transactions?search=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->getJson('/api/transactions?date_from=2026-09-10&date_to=2026-09-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');

        $this->getJson('/api/transactions?type=savings')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }
}
