<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_redirected_from_dashboard_to_onboarding(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('onboarding.show'));
    }

    public function test_new_user_is_redirected_from_products_to_onboarding(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/products')
            ->assertRedirect(route('onboarding.show'));
    }

    public function test_onboarding_page_is_reachable(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertOk();
    }

    public function test_complete_fails_when_steps_missing(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post('/onboarding/complete')
            ->assertStatus(422);

        $this->assertNull($user->fresh()->onboarded_at);
    }

    public function test_complete_succeeds_with_shop_and_extension_only(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $shop = Shop::create(['name' => 'Test Shop', 'currency' => 'EUR', 'product_type' => 'physical']);
        $shop->users()->attach($user->id, ['role' => 'owner']);
        $user->createToken('Extension Auto-Connect');

        $this->actingAs($user)
            ->post('/onboarding/complete')
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->onboarded_at);
    }

    public function test_show_redirects_to_product_edit_when_product_imported(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $shop = Shop::create(['name' => 'Test Shop', 'currency' => 'EUR', 'product_type' => 'physical']);
        $shop->users()->attach($user->id, ['role' => 'owner']);
        $user->createToken('Extension Auto-Connect');

        $product = Product::create([
            'shop_id' => $shop->id,
            'title' => 'Imported Product',
            'price' => 9.99,
        ]);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertRedirect(route('products.edit', $product));

        $this->assertNotNull($user->fresh()->onboarded_at);
    }

    public function test_show_redirects_already_onboarded_user_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertRedirect(route('dashboard'));
    }

    public function test_already_onboarded_user_reaches_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_shop_store_redirects_back_to_onboarding_when_user_needs_it(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post('/shops', [
                'name' => 'My Shop',
                'currency' => 'EUR',
                'product_type' => 'physical',
            ])
            ->assertRedirect(route('onboarding.show'));
    }
}
