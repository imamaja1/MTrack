<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\TaxConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_page_is_accessible_to_operator()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $operator = User::factory()->create([
            'role' => 'operator',
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($operator)->get('/pos');
        $response->assertStatus(200);
    }

    public function test_can_add_product_to_cart_and_checkout()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $product = Product::create([
            'admin_id' => $admin->id,
            'sku' => 'TEST-01',
            'name' => 'Test Product',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'stock' => 10,
            'min_stock_alert' => 2,
        ]);

        Livewire::actingAs($admin)
            ->test('operator.pos')
            ->call('addToCart', $product->id)
            ->assertHasNoErrors('cart')
            ->call('checkout')
            ->assertHasNoErrors('cart');

        $this->assertDatabaseHas('transactions', [
            'admin_id' => $admin->id,
            'total_cost' => 10000,
            'total_amount' => 15000,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'qty' => 1,
            'stock_before' => 10,
            'stock_after' => 9,
        ]);

        $this->assertEquals(9, $product->fresh()->stock);
    }

    public function test_cannot_checkout_if_out_of_stock()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $product = Product::create([
            'admin_id' => $admin->id,
            'sku' => 'TEST-02',
            'name' => 'Test Product 2',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'stock' => 1,
            'min_stock_alert' => 2,
        ]);

        Livewire::actingAs($admin)
            ->test('operator.pos')
            ->call('addToCart', $product->id)
            ->set('cart.'.$product->id.'.qty', 2) // Attempt to set qty more than stock
            ->call('checkout')
            ->assertHasErrors('cart');

        $this->assertEquals(1, $product->fresh()->stock);
    }

    public function test_can_checkout_with_active_tax()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        TaxConfig::create([
            'admin_id' => $admin->id,
            'name' => 'PPN 11%',
            'type' => 'percentage',
            'value' => 11,
            'is_active' => true,
        ]);

        $product = Product::create([
            'admin_id' => $admin->id,
            'sku' => 'TEST-TAX',
            'name' => 'Product With Tax',
            'cost_price' => 10000,
            'selling_price' => 100000,
            'stock' => 10,
            'min_stock_alert' => 2,
        ]);

        Livewire::actingAs($admin)
            ->test('operator.pos')
            ->call('addToCart', $product->id)
            ->assertSet('tax_amount', 11000)
            ->assertSet('total', 111000)
            ->call('checkout')
            ->assertHasNoErrors('cart');

        $this->assertDatabaseHas('transactions', [
            'admin_id' => $admin->id,
            'total_cost' => 10000,
            'tax_amount' => 11000,
            'total_amount' => 111000,
            'profit' => 90000,
        ]);
    }
}
