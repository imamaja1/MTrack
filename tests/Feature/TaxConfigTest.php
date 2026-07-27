<?php

namespace Tests\Feature;

use App\Models\TaxConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_config_page_is_accessible_to_admin()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/tax-config');
        $response->assertStatus(200);
    }

    public function test_tax_config_page_is_not_accessible_to_operator()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $operator = User::factory()->create([
            'role' => 'operator',
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($operator)->get('/admin/tax-config');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_tax_config()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\TaxConfig::class)
            ->set('name', 'PPN 11%')
            ->set('type', 'percentage')
            ->set('value', '11')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tax_configs', [
            'admin_id' => $admin->id,
            'name' => 'PPN 11%',
            'type' => 'percentage',
            'value' => 11,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_tax_config()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $tax = TaxConfig::create([
            'admin_id' => $admin->id,
            'name' => 'PPN Lama',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\TaxConfig::class)
            ->call('openModal', $tax->id)
            ->set('name', 'PPN Baru')
            ->set('value', '12')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tax_configs', [
            'id' => $tax->id,
            'name' => 'PPN Baru',
            'value' => 12,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_toggle_tax_config_status()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $tax = TaxConfig::create([
            'admin_id' => $admin->id,
            'name' => 'PPN Toggle',
            'type' => 'percentage',
            'value' => 11,
            'is_active' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\TaxConfig::class)
            ->call('toggleStatus', $tax->id);

        $this->assertTrue($tax->fresh()->is_active);
    }

    public function test_admin_can_delete_tax_config()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $tax = TaxConfig::create([
            'admin_id' => $admin->id,
            'name' => 'PPN Hapus',
            'type' => 'percentage',
            'value' => 11,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\TaxConfig::class)
            ->call('confirmDelete', $tax->id)
            ->call('executeDelete');

        $this->assertDatabaseMissing('tax_configs', [
            'id' => $tax->id,
        ]);
    }
}
