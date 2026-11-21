<?php

namespace Tests\Feature;

use App\Filament\Pages\StockCardReport;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Support\StockCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockCardTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $stock): Product
    {
        $product = Product::forceCreate([
            'name' => 'Galon', 'sku' => uniqid('SKU'), 'image_url' => '-', 'product_type' => 'REFILL',
            'unit' => 'pcs', 'price' => 5000, 'cogs' => 1000, 'is_enabled' => true,
        ]);
        Inventory::updateOrCreate(['product_id' => $product->id], ['quantity' => $stock, 'low_stock_threshold' => 1]);
        return $product;
    }

    private function move(Product $p, int $before, int $change, string $reason, string $at): void
    {
        DB::table('inventory_movements')->insert([
            'product_id' => $p->id, 'quantity_before' => $before, 'quantity_change' => $change,
            'quantity_after' => $before + $change, 'reason' => $reason, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    public function test_stock_card_computes_period_flow(): void
    {
        $p = $this->product(23);
        $this->move($p, 0, 10, 'RESTOCK', '2026-09-30 10:00:00');
        $this->move($p, 10, 20, 'RESTOCK', '2026-10-02 10:00:00');
        $this->move($p, 30, -8, 'SALE', '2026-10-03 10:00:00');
        $this->move($p, 22, 2, 'RETURN', '2026-10-04 10:00:00');
        $this->move($p, 24, -1, 'DAMAGE', '2026-10-05 10:00:00');

        $row = StockCard::rows(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'))[0];

        $this->assertSame(10, $row['opening']);
        $this->assertSame(20, $row['restock']);
        $this->assertSame(2, $row['return']);
        $this->assertSame(8, $row['sale']);
        $this->assertSame(1, $row['damage']);
        $this->assertSame(23, $row['closing']);
    }

    public function test_opname_keeps_history_with_system_snapshot_and_difference(): void
    {
        $p = $this->product(20);
        $kasir = User::factory()->create();
        $kasir->forceFill(['role' => 'kasir'])->save();
        Sanctum::actingAs($kasir);

        $this->postJson('/api/inventory/opname', ['opname' => [['product_id' => $p->id, 'real_quantity' => 18]]])->assertOk();

        $this->assertSame(20, Inventory::where('product_id', $p->id)->value('quantity'));
        $this->assertSame(-2, (int) DB::table('stock_opnames')->value('difference'));

        $row = StockCard::rows(now()->startOfMonth(), now())[0];
        $this->assertSame(18, $row['opname_real']);
        $this->assertSame(20, $row['opname_system']);
        $this->assertSame(-2, $row['opname_difference']);
    }

    public function test_page_renders_for_owner(): void
    {
        $this->product(5);
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'owner'])->save();
        $this->actingAs($owner);

        \Livewire\Livewire::test(StockCardReport::class)->assertSuccessful()->assertSee('Galon');
    }
}
