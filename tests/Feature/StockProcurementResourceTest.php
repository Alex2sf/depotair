<?php

namespace Tests\Feature;

use App\Filament\Resources\StockProcurements\Pages\ListStockProcurements;
use App\Filament\Resources\StockProcurements\Pages\ViewStockProcurement;
use App\Filament\Resources\StockProcurements\StockProcurementResource;
use App\Models\Product;
use App\Models\StockProcurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class StockProcurementResourceTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $owner = User::factory()->create(['name' => 'Pak Owner']);
        $owner->forceFill(['role' => 'owner'])->save();
        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        $owner->assignRole('super_admin');
        return $owner;
    }

    private function procurement(string $number, string $status, User $by): StockProcurement
    {
        $product = Product::forceCreate([
            'name' => 'Galon ' . $number, 'sku' => uniqid('SKU'), 'image_url' => '-', 'product_type' => 'REFILL',
            'unit' => 'pcs', 'price' => 5000, 'cogs' => 1000, 'is_enabled' => true,
        ]);
        $p = StockProcurement::forceCreate([
            'procurement_number' => $number, 'requested_by' => $by->id, 'status' => $status,
            'total_estimated_amount' => 50000, 'total_approved_amount' => $status === 'PENDING' ? 0 : 40000,
            'approved_at' => $status === 'PENDING' ? null : now(), 'received_at' => $status === 'RECEIVED' ? now() : null,
        ]);
        DB::table('stock_procurement_items')->insert([
            'procurement_id' => $p->id, 'product_id' => $product->id, 'current_stock' => 3,
            'quantity_requested' => 10, 'unit_cogs_requested' => 5000, 'subtotal_requested' => 50000,
            'quantity_approved' => $status === 'PENDING' ? null : 8, 'unit_cogs_approved' => $status === 'PENDING' ? null : 5000,
            'subtotal_approved' => $status === 'PENDING' ? null : 40000, 'is_approved' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $p;
    }

    public function test_list_shows_procurements_with_status_and_filters_by_status(): void
    {
        $owner = $this->owner();
        $pending = $this->procurement('PR-001', 'PENDING', $owner);
        $received = $this->procurement('PR-002', 'RECEIVED', $owner);
        $this->actingAs($owner);

        Livewire::test(ListStockProcurements::class)
            ->assertCanSeeTableRecords([$pending, $received])
            ->assertSee('Menunggu persetujuan')
            ->assertSee('Selesai (barang diterima)')
            ->filterTable('status', 'RECEIVED')
            ->assertCanSeeTableRecords([$received])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_view_page_shows_items_and_people(): void
    {
        $owner = $this->owner();
        $p = $this->procurement('PR-003', 'APPROVED', $owner);
        $this->actingAs($owner);

        Livewire::test(ViewStockProcurement::class, ['record' => $p->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('PR-003')
            ->assertSee('Galon PR-003')
            ->assertSee('Disetujui, belum diterima')
            ->assertSee('Rp 40.000')
            ->assertSee('oleh Pak Owner')
            ->assertSee('Alur Pengajuan')
            ->assertSee('Menunggu barang datang')
            ->assertSee('−2'); // diajukan 10, disetujui 8
    }

    public function test_view_page_for_rejected_procurement_shows_rejection_flow(): void
    {
        $owner = $this->owner();
        $p = $this->procurement('PR-005', 'REJECTED', $owner);
        $this->actingAs($owner);

        Livewire::test(ViewStockProcurement::class, ['record' => $p->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('Ditolak')
            ->assertSee('Tidak dilanjutkan')
            ->assertSee('Total diajukan')
            ->assertSee('Tidak ada (pengajuan ditolak)')
            ->assertDontSee('diisi saat barang diterima');
    }

    public function test_resource_is_read_only(): void
    {
        $owner = $this->owner();
        $p = $this->procurement('PR-004', 'PENDING', $owner);

        $this->assertFalse(StockProcurementResource::canCreate());
        $this->assertFalse(StockProcurementResource::canEdit($p));
        $this->assertFalse(StockProcurementResource::canDelete($p));
        $this->assertArrayNotHasKey('edit', StockProcurementResource::getPages());
    }
}
