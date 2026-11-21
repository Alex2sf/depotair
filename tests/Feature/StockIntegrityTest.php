<?php

namespace Tests\Feature;

use App\Filament\Resources\Inventories\Pages\ListInventories;
use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use App\Models\CashBalance;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockIntegrityTest extends TestCase
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

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();
        return $user;
    }

    private function webOrder(Product $p, int $qty, string $type = 'SELF_PICKUP')
    {
        return $this->withSession(['cart' => [$p->id => ['name' => $p->name, 'price' => 5000, 'qty' => $qty]]])
            ->post('/submit-order', [
                'name' => 'Budi', 'phone' => '081234567890', 'order_type' => $type,
                'payment_type' => 'TUNAI', 'address' => 'Jl. Mawar',
            ]);
    }

    public function test_web_order_deducts_stock_and_cancel_restores_it(): void
    {
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => 0]);
        $p = $this->product(10);

        $this->webOrder($p, 3)->assertRedirect();
        $this->assertSame(7, Inventory::where('product_id', $p->id)->value('quantity'));
        $this->assertSame(-3, (int) InventoryMovement::where('reason', 'SALE')->value('quantity_change'));

        Sanctum::actingAs($this->user('kasir'));
        $number = Order::value('order_number');
        $this->postJson("/api/orders/{$number}/cancel")->assertOk();

        $this->assertSame(10, Inventory::where('product_id', $p->id)->value('quantity'));
        $this->assertSame(0, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
    }

    public function test_web_order_with_insufficient_stock_creates_nothing(): void
    {
        $p = $this->product(2);

        $this->webOrder($p, 5)->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(2, Inventory::where('product_id', $p->id)->value('quantity'));
    }

    public function test_web_pickup_cash_enters_drawer_when_completed(): void
    {
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => 0]);
        $p = $this->product(10);
        $this->webOrder($p, 2);
        $number = Order::value('order_number');

        Sanctum::actingAs($this->user('kasir'));
        $this->postJson("/api/orders/{$number}/ready")->assertOk();
        $this->postJson("/api/orders/{$number}/complete")->assertOk();

        $this->assertSame(10000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
    }

    public function test_filament_adjust_stock_logs_adjustment_with_notes(): void
    {
        $p = $this->product(20);
        $owner = $this->user('owner');
        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        $owner->assignRole('super_admin');
        $this->actingAs($owner);
        $inventory = Inventory::where('product_id', $p->id)->first();

        \Livewire\Livewire::test(ListInventories::class)
            ->callTableAction('adjust_stock', $inventory, data: ['target' => 17, 'notes' => 'Koreksi opname 22/11'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(17, $inventory->fresh()->quantity);
        $movement = InventoryMovement::where('reason', 'ADJUSTMENT')->first();
        $this->assertSame(-3, $movement->quantity_change);
        $this->assertSame('Koreksi opname 22/11', $movement->notes);
    }

    public function test_admin_edit_of_stock_is_logged_as_adjustment(): void
    {
        $p = $this->product(19);
        $owner = $this->user('owner');
        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        $owner->assignRole('super_admin');
        $this->actingAs($owner);
        $inventory = Inventory::where('product_id', $p->id)->first();

        \Livewire\Livewire::test(\App\Filament\Resources\Inventories\Pages\EditInventory::class, ['record' => $inventory->getRouteKey()])
            ->fillForm(['quantity' => 15])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(15, $inventory->fresh()->quantity);
        $this->assertSame(-4, (int) InventoryMovement::where('reason', 'ADJUSTMENT')->value('quantity_change'));
    }

    public function test_every_new_product_gets_an_inventory_row_with_zero_stock(): void
    {
        $product = Product::forceCreate([
            'name' => 'Kanebo', 'sku' => 'KNB-1', 'image_url' => '-', 'product_type' => 'CONSUMABLE',
            'unit' => 'pcs', 'price' => 1000, 'cogs' => 500, 'is_enabled' => true,
        ]);

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertNotNull($inventory);
        $this->assertSame(0, $inventory->quantity);
        $this->assertSame(0, $inventory->real_quantity);
    }

    public function test_backfill_migration_creates_missing_inventory_only(): void
    {
        $withStock = $this->product(7);
        $orphan = Product::forceCreate([
            'name' => 'Tanpa Stok', 'sku' => 'ORPHAN', 'image_url' => '-', 'product_type' => 'CONSUMABLE',
            'unit' => 'pcs', 'price' => 1000, 'cogs' => 500, 'is_enabled' => true,
        ]);
        Inventory::where('product_id', $orphan->id)->delete();

        (require base_path('database/migrations/2026_11_22_100000_backfill_missing_inventories.php'))->up();

        $this->assertSame(0, Inventory::where('product_id', $orphan->id)->value('quantity'));
        $this->assertSame(7, Inventory::where('product_id', $withStock->id)->value('quantity'));
        $this->assertSame(1, Inventory::where('product_id', $orphan->id)->count());
    }

    public function test_movement_history_is_read_only_in_admin(): void
    {
        $p = $this->product(5);
        $m = InventoryMovement::create([
            'product_id' => $p->id, 'quantity_before' => 5, 'quantity_change' => 1, 'quantity_after' => 6, 'reason' => 'RESTOCK',
        ]);

        $this->assertFalse(InventoryMovementResource::canCreate());
        $this->assertFalse(InventoryMovementResource::canEdit($m));
        $this->assertFalse(InventoryMovementResource::canDelete($m));
    }

    public function test_api_adjust_keeps_notes_and_new_product_initial_stock_is_logged(): void
    {
        $p = $this->product(5);
        Sanctum::actingAs($this->user('kasir'));

        $this->postJson('/api/inventory/adjust', ['product_id' => $p->id, 'quantity' => 2, 'reason' => 'RESTOCK', 'notes' => 'Datang dari supplier'])->assertOk();
        $this->assertSame('Datang dari supplier', InventoryMovement::latest('id')->value('notes'));

        $this->postJson('/api/products', ['name' => 'Solatip', 'price' => 2000, 'unit' => 'pcs', 'initial_stock' => 12])->assertOk();
        $newId = Product::where('name', 'Solatip')->value('id');
        $this->assertSame(12, Inventory::where('product_id', $newId)->value('quantity'));
        $this->assertSame(12, (int) DB::table('inventory_movements')->where('product_id', $newId)->sum('quantity_change'));
    }
}
