<?php

namespace Tests\Feature;

use App\Livewire\Manager\SupplierInvoices;
use App\Models\ServiceProvider;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_save_supplier_invoice(): void
    {
        $manager = User::factory()->manager()->create();
        $provider = ServiceProvider::factory()->withWaterRates()->create();

        Livewire::actingAs($manager)
            ->test(SupplierInvoices::class)
            ->set('service_provider_id', $provider->id)
            ->call('openInvoiceModal')
            ->set('form_year', 2026)
            ->set('form_month', 6)
            ->set('cold_m3', '120.500')
            ->set('cold_amount', '547.28')
            ->set('hot_m3', '85.200')
            ->set('hot_amount', '387.66')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('supplier_invoices', [
            'service_provider_id' => $provider->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 120.5,
            'cold_amount' => 547.28,
            'hot_m3' => 85.2,
            'hot_amount' => 387.66,
            'recorded_by_user_id' => $manager->id,
        ]);
    }

    public function test_comma_decimal_separator_is_normalized_on_save(): void
    {
        $manager = User::factory()->manager()->create();
        $provider = ServiceProvider::factory()->withWaterRates()->create();

        Livewire::actingAs($manager)
            ->test(SupplierInvoices::class)
            ->set('service_provider_id', $provider->id)
            ->call('openInvoiceModal')
            ->set('form_year', 2026)
            ->set('form_month', 6)
            ->set('cold_m3', '120,500')
            ->set('cold_amount', '547,28')
            ->set('hot_m3', '85,200')
            ->set('hot_amount', '387,66')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('supplier_invoices', [
            'service_provider_id' => $provider->id,
            'cold_m3' => 120.5,
            'cold_amount' => 547.28,
            'hot_m3' => 85.2,
            'hot_amount' => 387.66,
        ]);
    }

    public function test_manager_can_search_and_sort_invoices_by_period(): void
    {
        $manager = User::factory()->manager()->create();
        $provider = ServiceProvider::factory()->withWaterRates()->create();

        SupplierInvoice::query()->create([
            'service_provider_id' => $provider->id,
            'year' => 2026,
            'month' => 5,
            'cold_m3' => 10,
            'cold_amount' => 45.5,
            'hot_m3' => 5,
            'hot_amount' => 22.75,
            'recorded_by_user_id' => $manager->id,
        ]);

        SupplierInvoice::query()->create([
            'service_provider_id' => $provider->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 12,
            'cold_amount' => 54.6,
            'hot_m3' => 6,
            'hot_amount' => 27.3,
            'recorded_by_user_id' => $manager->id,
        ]);

        Livewire::actingAs($manager)
            ->test(SupplierInvoices::class)
            ->set('service_provider_id', $provider->id)
            ->set('search', '2026-06')
            ->assertSet('sortNewestFirst', true)
            ->assertSee('2026')
            ->call('toggleSort')
            ->assertSet('sortNewestFirst', false);
    }

    public function test_manager_can_save_non_water_supplier_invoice(): void
    {
        $manager = User::factory()->manager()->create();
        $provider = ServiceProvider::factory()->create(['name' => 'Atkritumu serviss']);
        $provider->rates()->create(['service_code' => 'garbage', 'price' => 12.50]);

        Livewire::actingAs($manager)
            ->test(SupplierInvoices::class)
            ->set('service_provider_id', $provider->id)
            ->call('openInvoiceModal')
            ->set('form_year', 2026)
            ->set('form_month', 7)
            ->set('total_amount', '350.00')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('supplier_invoices', [
            'service_provider_id' => $provider->id,
            'year' => 2026,
            'month' => 7,
            'total_amount' => 350.00,
        ]);
    }

    public function test_resident_cannot_access_invoices_page(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->get(route('manager.invoices'))
            ->assertForbidden();
    }
}
