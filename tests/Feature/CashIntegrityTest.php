<?php

namespace Tests\Feature;

use App\Models\CashBalance;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockProcurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();
        return $user;
    }

    private function product(int $stock, int $cogs = 1000): Product
    {
        $product = Product::forceCreate([
            'name' => 'Galon', 'sku' => uniqid('SKU'), 'image_url' => '-', 'product_type' => 'REFILL',
            'unit' => 'pcs', 'price' => 5000, 'cogs' => $cogs, 'is_enabled' => true,
        ]);
        Inventory::forceCreate(['product_id' => $product->id, 'quantity' => $stock, 'low_stock_threshold' => 1]);
        return $product;
    }

    private function balances(int $cashier, int $main): void
    {
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => $cashier]);
        CashBalance::updateOrCreate(['type' => 'MAIN'], ['balance' => $main]);
    }

    private function procurement(User $kasir, Product $product, int $qty, int $unitCogs, string $status = 'PENDING'): StockProcurement
    {
        $procurement = StockProcurement::forceCreate([
            'procurement_number' => uniqid('PR'), 'requested_by' => $kasir->id, 'status' => $status,
            'total_estimated_amount' => $qty * $unitCogs,
        ]);
        DB::table('stock_procurement_items')->insert([
            'procurement_id' => $procurement->id, 'product_id' => $product->id, 'quantity_requested' => $qty,
            'unit_cogs_requested' => $unitCogs, 'subtotal_requested' => $qty * $unitCogs,
            'quantity_approved' => $status === 'APPROVED' ? $qty : null, 'is_approved' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $procurement;
    }

    public function test_checkout_resent_with_same_client_ref_creates_one_order(): void
    {
        $this->balances(0, 0);
        $kasir = $this->user('kasir');
        $product = $this->product(10);
        $customer = Customer::forceCreate(['name' => 'Budi', 'phone_number' => '0811']);
        Sanctum::actingAs($kasir);

        $payload = [
            'client_ref' => 'abc123', 'customer_id' => $customer->id, 'order_type' => 'SELF_PICKUP',
            'payment_type' => 'TUNAI', 'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $first = $this->postJson('/api/orders/checkout', $payload)->assertOk();
        $second = $this->postJson('/api/orders/checkout', $payload)->assertOk();

        $this->assertSame($first->json('order.order_number'), $second->json('order.order_number'));
        $this->assertSame(1, Order::count());
        $this->assertSame(8, Inventory::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(10000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
    }

    public function test_approve_with_insufficient_main_balance_changes_nothing(): void
    {
        $this->balances(0, 1000);
        $owner = $this->user('owner');
        $product = $this->product(0, 1000);
        $procurement = $this->procurement($this->user('kasir'), $product, 5, 2000);
        Sanctum::actingAs($owner);

        $this->postJson("/api/procurements/{$procurement->id}/review", ['action' => 'APPROVE'])->assertStatus(422);

        $this->assertSame('PENDING', $procurement->fresh()->status);
        $this->assertNull(DB::table('stock_procurement_items')->value('quantity_approved'));
        $this->assertSame(1000, (int) $product->fresh()->cogs);
        $this->assertSame(1000, (int) CashBalance::where('type', 'MAIN')->value('balance'));
    }

    public function test_second_approve_does_not_deduct_main_balance_again(): void
    {
        $this->balances(0, 100000);
        $product = $this->product(0, 1000);
        $procurement = $this->procurement($this->user('kasir'), $product, 5, 1000);
        Sanctum::actingAs($this->user('owner'));

        $this->postJson("/api/procurements/{$procurement->id}/review", ['action' => 'APPROVE'])->assertOk();
        $this->postJson("/api/procurements/{$procurement->id}/review", ['action' => 'APPROVE'])->assertStatus(422);

        $this->assertSame(95000, (int) CashBalance::where('type', 'MAIN')->value('balance'));
    }

    public function test_cancel_restores_stock_and_cash_once(): void
    {
        $this->balances(0, 0);
        $kasir = $this->user('kasir');
        $product = $this->product(10);
        $customer = Customer::forceCreate(['name' => 'Budi', 'phone_number' => '0812']);
        Sanctum::actingAs($kasir);

        $number = $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'SELF_PICKUP', 'payment_type' => 'TUNAI',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk()->json('order.order_number');

        $this->postJson("/api/orders/{$number}/cancel")->assertOk();
        $this->postJson("/api/orders/{$number}/cancel")->assertStatus(422);

        $this->assertSame(10, Inventory::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(0, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
        // Riwayat stok harus konsisten: -2 (jual) lalu +2 (batal)
        $this->assertSame(0, (int) DB::table('inventory_movements')->where('product_id', $product->id)->sum('quantity_change'));
        $this->assertSame(1, DB::table('inventory_movements')->where('reason', 'RETURN')->count());
    }

    public function test_history_search_respects_status_and_date_filters(): void
    {
        $this->balances(0, 0);
        $kasir = $this->user('kasir');
        $product = $this->product(10);
        $customer = Customer::forceCreate(['name' => 'Siti', 'phone_number' => '0813']);
        Sanctum::actingAs($kasir);

        $checkout = fn () => $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'SELF_PICKUP', 'payment_type' => 'TUNAI',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->json('order.order_number');

        $keep = $checkout();
        $cancelled = $checkout();
        $this->postJson("/api/orders/{$cancelled}/cancel")->assertOk();
        $old = $checkout();
        Order::where('order_number', $old)->update(['created_at' => now()->subDays(10)]);

        $numbers = collect($this->getJson('/api/orders/history?search=Siti&start_date=' . now()->subDay()->toDateString())
            ->assertOk()->json('data'))->pluck('order_number')->all();

        $this->assertSame([$keep], $numbers);
    }

    public function test_second_close_shift_is_rejected(): void
    {
        $this->balances(5000, 0);
        Sanctum::actingAs($this->user('kasir'));

        $this->getJson('/api/shifts/current')->assertOk();
        $this->postJson('/api/shifts/close', ['actual_cash' => 5000])->assertOk();
        $this->postJson('/api/shifts/close', ['actual_cash' => 1])->assertStatus(404);

        $this->assertSame(5000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
    }

    public function test_cod_cash_enters_drawer_only_when_delivery_completes(): void
    {
        $this->balances(0, 0);
        $kasir = $this->user('kasir');
        $product = $this->product(10);
        $customer = Customer::forceCreate(['name' => 'Wiyono', 'phone_number' => '0814']);
        Sanctum::actingAs($kasir);

        $number = $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'DELIVERY', 'payment_type' => 'TUNAI',
            'delivery_address' => 'Jl. Mawar 1', 'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk()->json('order.order_number');

        $this->assertSame(0, (int) CashBalance::where('type', 'CASHIER')->value('balance'));

        $this->postJson("/api/orders/{$number}/ready")->assertOk();
        $this->postJson("/api/orders/{$number}/complete")->assertOk();
        $this->postJson("/api/orders/{$number}/complete")->assertStatus(404);

        $this->assertSame(10000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
        $this->assertSame(1, DB::table('cash_transactions')->where('description', 'like', "Penjualan TUNAI #{$number}%")->count());
    }

    public function test_courier_completion_records_cod_cash(): void
    {
        $this->balances(0, 0);
        $product = $this->product(10);
        $customer = Customer::forceCreate(['name' => 'Windy', 'phone_number' => '0817']);
        Sanctum::actingAs($this->user('kasir'));

        $number = $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'DELIVERY', 'payment_type' => 'TUNAI',
            'delivery_address' => 'Jl. Melati 2', 'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->json('order.order_number');
        $this->postJson("/api/orders/{$number}/ready")->assertOk();

        Sanctum::actingAs($kurir = $this->user('kurir'));
        $this->postJson("/api/courier/orders/{$number}/pickup")->assertOk();
        $this->post("/api/courier/orders/{$number}/complete", [
            'image' => \Illuminate\Http\UploadedFile::fake()->image('bukti.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(5000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
        $this->assertSame($kurir->id, (int) DB::table('cash_transactions')->where('order_id', '>', 0)->value('recorded_by'));
    }

    public function test_cancel_undelivered_cod_does_not_refund_drawer(): void
    {
        $this->balances(30000, 0);
        $kasir = $this->user('kasir');
        $product = $this->product(10);
        $customer = Customer::forceCreate(['name' => 'Wiyono', 'phone_number' => '0815']);
        Sanctum::actingAs($kasir);

        $number = $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'DELIVERY', 'payment_type' => 'TUNAI',
            'delivery_address' => 'Jl. Mawar 1', 'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->json('order.order_number');

        $this->postJson("/api/orders/{$number}/cancel")->assertOk();

        $this->assertSame(30000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
        $this->assertSame(10, Inventory::where('product_id', $product->id)->value('quantity'));
    }

    public function test_shift_expected_cash_matches_drawer_balance(): void
    {
        $this->balances(0, 0);
        $kasir = $this->user('kasir');
        $product = $this->product(10);
        $customer = Customer::forceCreate(['name' => 'Budi', 'phone_number' => '0816']);
        Sanctum::actingAs($kasir);

        $this->getJson('/api/shifts/current')->assertOk();
        $this->postJson('/api/cash/transaction', ['type' => 'DEPOSIT', 'amount' => 100000, 'description' => 'Modal awal kasir'])->assertOk();
        $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'SELF_PICKUP', 'payment_type' => 'TUNAI',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertOk();
        $this->postJson('/api/cash/deposit-to-main', ['amount' => 40000])->assertOk();

        $data = $this->postJson('/api/shifts/close', ['actual_cash' => 75000])->assertOk()->json('data');

        $this->assertSame(75000, $data['expected_cash']);
        $this->assertSame(75000, (int) DB::table('cashier_shifts')->value('system_balance_at_close'));
        $this->assertSame(75000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
    }

    public function test_checkout_with_insufficient_stock_returns_422_without_creating_order(): void
    {
        $this->balances(0, 0);
        $product = $this->product(1);
        $customer = Customer::forceCreate(['name' => 'Budi', 'phone_number' => '0818']);
        Sanctum::actingAs($this->user('kasir'));

        $res = $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'SELF_PICKUP', 'payment_type' => 'TUNAI',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);
        $res->assertStatus(422);
        $this->assertStringContainsString('tidak cukup', $res->json('message'));

        $this->assertSame(0, Order::count());
        $this->assertSame(0, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
    }

    public function test_input_validations_reject_bad_values(): void
    {
        $this->balances(50000, 0);
        $product = $this->product(3);
        $customer = Customer::forceCreate(['name' => 'Budi', 'phone_number' => '0819']);
        Sanctum::actingAs($this->user('kasir'));

        $this->postJson('/api/orders/checkout', [
            'customer_id' => $customer->id, 'order_type' => 'DELIVERY', 'payment_type' => 'TUNAI',
            'delivery_address' => 'Jl. Mawar', 'delivery_scheduled_at' => now()->subDay()->toIso8601String(),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('delivery_scheduled_at');

        $this->postJson('/api/cash/deposit-to-main', ['amount' => 0])->assertStatus(422);

        $this->postJson('/api/inventory/adjust', ['product_id' => $product->id, 'quantity' => 5, 'reason' => 'DAMAGE'])
            ->assertStatus(422);
        $this->assertSame(3, Inventory::where('product_id', $product->id)->value('quantity'));

        $this->getJson('/api/test-checkout-code')->assertNotFound();
    }

    public function test_manual_cash_in_with_word_modal_does_not_touch_kas_besar(): void
    {
        $this->balances(0, 500000);
        Sanctum::actingAs($this->user('kasir'));

        $this->postJson('/api/cash/transaction', ['type' => 'DEPOSIT', 'amount' => 100000, 'description' => 'Modal awal kasir'])->assertOk();

        $this->assertSame(100000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
        $this->assertSame(500000, (int) CashBalance::where('type', 'MAIN')->value('balance'));
    }

    public function test_second_receive_does_not_add_stock_again(): void
    {
        $product = $this->product(0);
        $kasir = $this->user('kasir');
        $procurement = $this->procurement($kasir, $product, 5, 1000, 'APPROVED');
        Sanctum::actingAs($kasir);

        $this->postJson("/api/procurements/{$procurement->id}/receive")->assertOk();
        $this->postJson("/api/procurements/{$procurement->id}/receive")->assertStatus(422);

        $this->assertSame(5, Inventory::where('product_id', $product->id)->value('quantity'));
    }
}
