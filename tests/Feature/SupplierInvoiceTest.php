<?php

namespace Tests\Feature;

use App\Livewire\Manager\SupplierInvoices;
use App\Models\ServiceProvider;
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
            ->set('year', 2026)
            ->set('month', 6)
            ->call('openInvoiceModal')
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

    public function test_resident_cannot_access_invoices_page(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->get(route('manager.invoices'))
            ->assertForbidden();
    }
}
