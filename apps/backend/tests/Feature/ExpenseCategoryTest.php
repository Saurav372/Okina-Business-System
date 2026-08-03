<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $financeStaff;

    private User $salesStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);

        // Finance Staff has the 'finance.manage_expenses' permission
        $this->financeStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->financeStaff->assignRole(Role::FINANCE_STAFF);

        // Sales Staff does NOT have the 'finance.manage_expenses' permission
        $this->salesStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->salesStaff->assignRole(Role::SALES_STAFF);
    }

    public function test_default_categories_are_seeded_idempotently(): void
    {
        $this->assertDatabaseCount('expense_categories', 0);

        // Run seeder first time
        $this->seed(ExpenseCategorySeeder::class);
        $this->assertDatabaseCount('expense_categories', 6);

        // Run seeder second time
        $this->seed(ExpenseCategorySeeder::class);
        $this->assertDatabaseCount('expense_categories', 6);
    }

    public function test_seeder_regression_behavior(): void
    {
        $this->seed(ExpenseCategorySeeder::class);

        $category = ExpenseCategory::query()->where('code', 'shipping')->firstOrFail();
        $originalPublicId = $category->public_id;

        // Manually update name and description
        $category->update([
            'name' => 'Custom Name',
            'description' => 'Custom Description',
        ]);

        // Run seeder again
        $this->seed(ExpenseCategorySeeder::class);

        $category->refresh();
        $this->assertSame('Shipping & Logistics', $category->name);
        $this->assertSame('Shipping, freight, delivery, and postage expenses', $category->description);
        $this->assertSame($originalPublicId, $category->public_id);
        $this->assertSame('shipping', $category->code);
    }

    public function test_category_crud_actions_for_authorized_users(): void
    {
        // 1. Create (Store)
        $payload = [
            'name' => 'Marketing Ads',
            'code' => 'marketing-ads',
            'description' => 'Online ads expenses',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->financeStaff)
            ->postJson('/admin/expense-categories', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Marketing Ads');
        $response->assertJsonPath('data.code', 'marketing-ads');
        $response->assertJsonPath('data.is_active', true);

        $publicId = $response->json('data.public_id');
        $this->assertMatchesRegularExpression('/^EXPCAT-[A-Z0-9]{12}$/', $publicId);

        // 2. Show
        $response = $this->actingAs($this->financeStaff)
            ->getJson("/admin/expense-categories/{$publicId}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.public_id', $publicId);
        $response->assertJsonPath('data.name', 'Marketing Ads');

        // 3. Update (PATCH) - update name, description, and status
        $updatePayload = [
            'name' => 'Promotional Ads',
            'description' => 'Updated ads description',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->financeStaff)
            ->patchJson("/admin/expense-categories/{$publicId}", $updatePayload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Promotional Ads');
        $response->assertJsonPath('data.description', 'Updated ads description');
        $response->assertJsonPath('data.is_active', false);

        // 4. Destroy (DELETE)
        $response = $this->actingAs($this->financeStaff)
            ->deleteJson("/admin/expense-categories/{$publicId}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Expense category soft deleted successfully.');

        $this->assertSoftDeleted('expense_categories', ['public_id' => $publicId]);
    }

    public function test_category_list_results_are_sorted_alphabetically(): void
    {
        ExpenseCategory::create(['name' => 'Zebra Supplies', 'code' => 'zebra']);
        ExpenseCategory::create(['name' => 'Apple Logistics', 'code' => 'apple']);
        ExpenseCategory::create(['name' => 'Banana Marketing', 'code' => 'banana']);

        $response = $this->actingAs($this->financeStaff)
            ->getJson('/admin/expense-categories');

        $response->assertStatus(200);
        $names = $response->json('data.*.name');

        $this->assertSame(['Apple Logistics', 'Banana Marketing', 'Zebra Supplies'], $names);
    }

    public function test_category_crud_denied_for_unauthorized_users(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Overhead',
            'code' => 'office-overhead',
        ]);

        $this->actingAs($this->salesStaff)
            ->getJson('/admin/expense-categories')
            ->assertStatus(403);

        $this->actingAs($this->salesStaff)
            ->postJson('/admin/expense-categories', ['name' => 'New', 'code' => 'new'])
            ->assertStatus(403);

        $this->actingAs($this->salesStaff)
            ->getJson("/admin/expense-categories/{$category->public_id}")
            ->assertStatus(403);

        $this->actingAs($this->salesStaff)
            ->patchJson("/admin/expense-categories/{$category->public_id}", ['name' => 'New'])
            ->assertStatus(403);

        $this->actingAs($this->salesStaff)
            ->deleteJson("/admin/expense-categories/{$category->public_id}")
            ->assertStatus(403);
    }

    public function test_unauthorized_users_cannot_infer_existence(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Overhead',
            'code' => 'office-overhead',
        ]);

        // Querying a valid public ID as an unauthorized user returns 403
        $this->actingAs($this->salesStaff)
            ->getJson("/admin/expense-categories/{$category->public_id}")
            ->assertStatus(403);

        // Querying a nonexistent ID as an unauthorized user returns 404
        $this->actingAs($this->salesStaff)
            ->getJson('/admin/expense-categories/EXPCAT-NONEXISTENT')
            ->assertStatus(404);
    }

    public function test_soft_deleted_categories_not_resolved_by_route_binding(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Overhead',
            'code' => 'office-overhead',
        ]);
        $category->delete();

        $this->actingAs($this->financeStaff)
            ->getJson("/admin/expense-categories/{$category->public_id}")
            ->assertStatus(404);
    }

    public function test_uniqueness_rules_normalize_whitespace_and_slug(): void
    {
        ExpenseCategory::create([
            'name' => 'Shipping Supplies',
            'code' => 'shipping',
        ]);

        $variations = [
            'shipping',
            'Shipping',
            ' SHIPPING',
            'shipping-',
            'Shipping!!!',
            'shipping_',
        ];

        foreach ($variations as $variation) {
            $response = $this->actingAs($this->financeStaff)
                ->postJson('/admin/expense-categories', [
                    'name' => 'Duplicate Category',
                    'code' => $variation,
                ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors('code');
        }
    }

    public function test_normalization_of_complex_punctuation(): void
    {
        $response = $this->actingAs($this->financeStaff)
            ->postJson('/admin/expense-categories', [
                'name' => 'Shipping & Logistics Support',
                'code' => ' Shipping & Logistics ',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'shipping-logistics');
    }

    public function test_soft_deleted_categories_reserve_their_code(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Overhead',
            'code' => 'office-overhead',
        ]);
        $category->delete();

        $response = $this->actingAs($this->financeStaff)
            ->postJson('/admin/expense-categories', [
                'name' => 'New Office Overhead',
                'code' => 'office-overhead',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('code');
    }

    public function test_code_immutability(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Overhead',
            'code' => 'OFFICE_OVERHEAD',
        ]);

        // 1. API: PATCH request containing code is rejected with 422 (code is prohibited on update)
        $response = $this->actingAs($this->financeStaff)
            ->patchJson("/admin/expense-categories/{$category->public_id}", [
                'name' => 'Updated Overhead',
                'code' => 'NEW_OFFICE_CODE',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('code');

        // 2. Direct model assignment throws LogicException
        $this->expectException(\LogicException::class);
        $category->save();
    }

    public function test_public_id_immutability(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Overhead',
            'code' => 'office-overhead',
        ]);
        $originalPublicId = $category->public_id;

        $response = $this->actingAs($this->financeStaff)
            ->patchJson("/admin/expense-categories/{$category->public_id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.public_id', $originalPublicId);

        $category->refresh();
        $this->assertSame($originalPublicId, $category->public_id);
    }

    public function test_referenced_deletion_is_blocked(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Overhead',
            'code' => 'office-overhead',
        ]);

        // Set mock referenced behavior to true
        ExpenseCategory::$mockReferenced = true;

        try {
            $response = $this->actingAs($this->financeStaff)
                ->deleteJson("/admin/expense-categories/{$category->public_id}");

            $response->assertStatus(422);
            $response->assertJsonValidationErrors('category');
            $response->assertJsonPath('errors.category.0', 'Expense category is referenced by existing expenses.');

            // Assert not soft-deleted
            $this->assertDatabaseHas('expense_categories', [
                'id' => $category->id,
                'deleted_at' => null,
            ]);
        } finally {
            ExpenseCategory::$mockReferenced = false;
        }
    }
}
