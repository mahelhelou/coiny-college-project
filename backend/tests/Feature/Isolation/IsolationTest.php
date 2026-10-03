<?php

namespace Tests\Feature\Isolation;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * BR-18: user B must never see, change or use anything that belongs to user A.
 */
class IsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    private Category $aliceCategory;

    private Transaction $aliceTransaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-15 12:00:00');
        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();
        $this->aliceCategory = Category::factory()->for($this->alice)->expense()->create(['name' => 'Alice Food']);
        $this->aliceTransaction = Transaction::factory()->inCategory($this->aliceCategory)->create([
            'amount' => '100.00', 'transaction_date' => '2026-09-10',
        ]);

        Sanctum::actingAs($this->bob);
    }

    public function test_category_list_excludes_other_users_categories(): void
    {
        Category::factory()->for($this->bob)->create(['name' => 'Bob Food']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Alice Food']);
    }

    public function test_cannot_rename_other_users_category(): void
    {
        $this->putJson("/api/categories/{$this->aliceCategory->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->putJson("/api/categories/{$this->aliceCategory->id}", [])->assertNotFound();

        $this->assertSame('Alice Food', $this->aliceCategory->fresh()->name);
    }

    public function test_cannot_delete_other_users_category(): void
    {
        $unused = Category::factory()->for($this->alice)->create();

        $this->deleteJson("/api/categories/{$unused->id}")->assertNotFound();
        $this->deleteJson("/api/categories/{$this->aliceCategory->id}")->assertNotFound();

        $this->assertModelExists($unused);
    }

    public function test_category_names_are_unique_per_user_only(): void
    {
        $this->postJson('/api/categories', ['name' => 'Alice Food', 'type' => 'expense'])->assertCreated();
    }

    public function test_cannot_read_other_users_transaction(): void
    {
        $this->getJson("/api/transactions/{$this->aliceTransaction->id}")->assertNotFound();
    }

    public function test_cannot_update_other_users_transaction(): void
    {
        $bobCategory = Category::factory()->for($this->bob)->expense()->create();

        $this->putJson("/api/transactions/{$this->aliceTransaction->id}", [
            'type' => 'expense',
            'amount' => '1.00',
            'category_id' => $bobCategory->id,
            'transaction_date' => '2026-09-10',
        ])->assertNotFound();

        $this->assertSame('100.00', $this->aliceTransaction->fresh()->amount);
    }

    public function test_cannot_delete_other_users_transaction(): void
    {
        $this->deleteJson("/api/transactions/{$this->aliceTransaction->id}")->assertNotFound();

        $this->assertModelExists($this->aliceTransaction);
    }

    public function test_transaction_list_excludes_other_users_rows(): void
    {
        $bobCategory = Category::factory()->for($this->bob)->expense()->create();
        $bobTransaction = Transaction::factory()->inCategory($bobCategory)->create();

        $this->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bobTransaction->id);

        $this->getJson("/api/transactions?category_id={$this->aliceCategory->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_cannot_create_transaction_with_other_users_category(): void
    {
        $this->postJson('/api/transactions', [
            'type' => 'expense',
            'amount' => '10.00',
            'category_id' => $this->aliceCategory->id,
            'transaction_date' => '2026-09-10',
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');

        $this->assertSame(0, $this->bob->transactions()->count());
    }

    public function test_dashboards_do_not_mix(): void
    {
        $bobCategory = Category::factory()->for($this->bob)->expense()->create(['name' => 'Bob Food']);
        Transaction::factory()->inCategory($bobCategory)->create(['amount' => '7.00', 'transaction_date' => '2026-09-11']);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.expenses', '7.00')
            ->assertJsonPath('data.totals.transactions_count', 1)
            ->assertJsonPath('data.all_time_balance', '-7.00')
            ->assertJsonCount(1, 'data.expense_by_category')
            ->assertJsonPath('data.expense_by_category.0.name', 'Bob Food')
            ->assertJsonCount(1, 'data.recent_transactions');
    }

    public function test_deleting_a_user_cascades_only_their_data(): void
    {
        $this->alice->delete();

        $this->assertModelMissing($this->aliceTransaction);
        $this->assertModelMissing($this->aliceCategory);
        $this->assertModelExists($this->bob);
    }
}
