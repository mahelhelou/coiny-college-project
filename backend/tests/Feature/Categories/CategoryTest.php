<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = Sanctum::actingAs(User::factory()->create());
    }

    public function test_lists_categories_with_transaction_counts(): void
    {
        $food = Category::factory()->for($this->user)->expense()->create(['name' => 'Food']);
        Category::factory()->for($this->user)->income()->create(['name' => 'Salary']);
        Transaction::factory()->count(3)->inCategory($food)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Food')
            ->assertJsonPath('data.0.type', 'expense')
            ->assertJsonPath('data.0.transactions_count', 3)
            ->assertJsonPath('data.1.transactions_count', 0);
    }

    public function test_lists_can_be_filtered_by_type(): void
    {
        Category::factory()->for($this->user)->expense()->create();
        Category::factory()->for($this->user)->income()->create(['name' => 'Salary']);

        $this->getJson('/api/categories?type=income')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Salary');

        $this->getJson('/api/categories?type=bogus')->assertUnprocessable();
    }

    public function test_creates_a_category(): void
    {
        $this->postJson('/api/categories', ['name' => '  Pets  ', 'type' => 'expense'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Pets')
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.transactions_count', 0);

        $this->assertDatabaseHas('categories', ['user_id' => $this->user->id, 'name' => 'Pets']);
    }

    public function test_create_validates_name_and_type(): void
    {
        $this->postJson('/api/categories', ['name' => '', 'type' => 'savings'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type']);

        $this->postJson('/api/categories', ['name' => str_repeat('a', 51), 'type' => 'expense'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_duplicate_name_in_same_type_is_rejected_case_insensitively(): void
    {
        Category::factory()->for($this->user)->expense()->create(['name' => 'Food']);

        $this->postJson('/api/categories', ['name' => ' food ', 'type' => 'expense'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_same_name_is_allowed_in_the_other_type(): void
    {
        Category::factory()->for($this->user)->expense()->create(['name' => 'Other']);

        $this->postJson('/api/categories', ['name' => 'Other', 'type' => 'income'])->assertCreated();
    }

    public function test_renames_a_category(): void
    {
        $category = Category::factory()->for($this->user)->expense()->create(['name' => 'Food']);

        $this->putJson("/api/categories/{$category->id}", ['name' => 'Groceries'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Groceries');
    }

    public function test_rename_ignores_type(): void
    {
        $category = Category::factory()->for($this->user)->expense()->create(['name' => 'Food']);

        $this->putJson("/api/categories/{$category->id}", ['name' => 'Food', 'type' => 'income'])
            ->assertOk()
            ->assertJsonPath('data.type', 'expense');

        $this->assertSame('expense', $category->fresh()->type->value);
    }

    public function test_rename_to_an_existing_name_in_same_type_is_rejected(): void
    {
        Category::factory()->for($this->user)->expense()->create(['name' => 'Food']);
        $bills = Category::factory()->for($this->user)->expense()->create(['name' => 'Bills']);

        $this->putJson("/api/categories/{$bills->id}", ['name' => 'FOOD'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        // Changing only the case of its own name is fine.
        $this->putJson("/api/categories/{$bills->id}", ['name' => 'BILLS'])->assertOk();
    }

    public function test_deletes_an_unused_category(): void
    {
        $category = Category::factory()->for($this->user)->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertNoContent();

        $this->assertModelMissing($category);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $category = Category::factory()->for($this->user)->create();
        Transaction::factory()->inCategory($category)->create();

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertConflict()
            ->assertJsonStructure(['message']);

        $this->assertModelExists($category);
    }

    public function test_missing_category_returns_404(): void
    {
        $this->putJson('/api/categories/999999', ['name' => 'X'])->assertNotFound();
        $this->deleteJson('/api/categories/999999')->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/categories')->assertUnauthorized();
    }
}
