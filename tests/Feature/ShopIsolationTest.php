<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that users can only access their own shops.
     */
    public function test_user_can_access_own_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::create([
            'name' => 'Test Shop',
            'currency' => 'EUR',
        ]);

        // Attach user to shop
        $user->shops()->attach($shop->id, ['role' => 'owner']);

        $this->assertTrue($user->hasAccessToShop($shop));
    }

    /**
     * Test that users cannot access shops they don't belong to.
     */
    public function test_user_cannot_access_other_users_shop(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $shop1 = Shop::create([
            'name' => 'User 1 Shop',
            'currency' => 'EUR',
        ]);

        // Only user1 has access to shop1
        $user1->shops()->attach($shop1->id, ['role' => 'owner']);

        $this->assertTrue($user1->hasAccessToShop($shop1));
        $this->assertFalse($user2->hasAccessToShop($shop1));
    }

    /**
     * Test shop policy view authorization.
     */
    public function test_user_can_view_authorized_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::create([
            'name' => 'Authorized Shop',
            'currency' => 'EUR',
        ]);

        $user->shops()->attach($shop->id, ['role' => 'member']);

        $this->actingAs($user);
        $this->assertTrue($user->can('view', $shop));
    }

    /**
     * Test shop policy denies unauthorized access.
     */
    public function test_user_cannot_view_unauthorized_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::create([
            'name' => 'Unauthorized Shop',
            'currency' => 'EUR',
        ]);

        $this->actingAs($user);
        $this->assertFalse($user->can('view', $shop));
    }

    /**
     * Test only owner can delete shop.
     */
    public function test_only_owner_can_delete_shop(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $shop = Shop::create([
            'name' => 'Test Shop',
            'currency' => 'EUR',
        ]);

        $owner->shops()->attach($shop->id, ['role' => 'owner']);
        $admin->shops()->attach($shop->id, ['role' => 'admin']);
        $member->shops()->attach($shop->id, ['role' => 'member']);

        $this->assertTrue($owner->can('delete', $shop));
        $this->assertFalse($admin->can('delete', $shop));
        $this->assertFalse($member->can('delete', $shop));
    }

    /**
     * Test owner and admin can update shop.
     */
    public function test_owner_and_admin_can_update_shop(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $shop = Shop::create([
            'name' => 'Test Shop',
            'currency' => 'EUR',
        ]);

        $owner->shops()->attach($shop->id, ['role' => 'owner']);
        $admin->shops()->attach($shop->id, ['role' => 'admin']);
        $member->shops()->attach($shop->id, ['role' => 'member']);

        $this->assertTrue($owner->can('update', $shop));
        $this->assertTrue($admin->can('update', $shop));
        $this->assertFalse($member->can('update', $shop));
    }
}
