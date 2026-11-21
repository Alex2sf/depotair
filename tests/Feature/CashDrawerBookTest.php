<?php

namespace Tests\Feature;

use App\Filament\Pages\CashDrawerBook;
use App\Models\CashBalance;
use App\Models\CashDrawerLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashDrawerBookTest extends TestCase
{
    use RefreshDatabase;

    private function tx(string $type, string $cashType, int $amount, string $desc, string $at): void
    {
        DB::table('cash_transactions')->insert([
            'type' => $type, 'cash_type' => $cashType, 'amount' => $amount, 'description' => $desc,
            'recorded_by' => User::factory()->create()->id, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    public function test_ledger_running_balance_goes_up_and_down_with_drawer_cash_only(): void
    {
        $this->tx('DEPOSIT', 'CASHIER', 200000, 'Modal awal kasir', '2026-10-01 07:00:00');
        $this->tx('DEPOSIT', 'CASHIER', 25000, 'Penjualan TUNAI #ORD1', '2026-10-01 08:00:00');
        $this->tx('EXPENSE', 'CASHIER', 20000, 'Operasional: bensin', '2026-10-01 11:00:00');
        $this->tx('DEPOSIT', 'CASHIER', 150000, 'Setor ke kas besar atas nama Kasir A', '2026-10-01 20:00:00');
        $this->tx('EXPENSE', 'MAIN', 999000, 'Pengadaan Stok #PR-1', '2026-10-01 12:00:00');

        $rows = CashDrawerLedger::orderBy('transaction_date')->orderBy('id')->get();

        $this->assertSame([200000, 225000, 205000, 55000], $rows->pluck('running_balance')->all());
        $this->assertSame('Setor Kas Besar', $rows->last()->category);
        $this->assertSame(150000, $rows->last()->amount_out);
    }

    public function test_reconciliation_flags_balance_changed_without_transaction(): void
    {
        $this->tx('DEPOSIT', 'CASHIER', 50000, 'Modal awal kasir', '2026-10-01 07:00:00');
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => 45000]);

        $this->assertSame(['book' => 50000, 'system' => 45000, 'difference' => -5000], CashDrawerBook::reconciliation());
    }

    public function test_close_shift_snapshots_balance_without_overwriting_it(): void
    {
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => 55000]);
        CashBalance::updateOrCreate(['type' => 'MAIN'], ['balance' => 0]);
        $kasir = User::factory()->create();
        $kasir->forceFill(['role' => 'kasir'])->save();
        Sanctum::actingAs($kasir);

        $this->getJson('/api/shifts/current')->assertOk();
        $this->postJson('/api/shifts/close', ['actual_cash' => 50000])->assertOk()
            ->assertJsonPath('data.difference', 50000 - 55000);

        $this->assertSame(55000, (int) CashBalance::where('type', 'CASHIER')->value('balance'));
        $this->assertSame(55000, (int) DB::table('cashier_shifts')->value('system_balance_at_close'));
    }

    public function test_owner_adjusts_book_and_system_to_counted_cash(): void
    {
        $this->tx('DEPOSIT', 'CASHIER', 9821499, 'Penjualan TUNAI #ORD1', '2026-11-01 08:00:00');
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => 6713000]);
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'owner'])->save();
        $this->actingAs($owner);

        \Livewire\Livewire::test(CashDrawerBook::class)
            ->callTableAction('adjust_to_counted', data: ['counted' => 6700000, 'note' => 'Patok saldo awal'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(['book' => 6700000, 'system' => 6700000, 'difference' => 0], CashDrawerBook::reconciliation());
        $this->assertSame(3121499, (int) DB::table('cash_transactions')->where('description', 'like', 'Penyesuaian saldo laci%')->value('amount'));
    }

    public function test_adjust_action_hidden_for_kasir(): void
    {
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => 0]);
        $kasir = User::factory()->create();
        $kasir->forceFill(['role' => 'kasir'])->save();
        $this->actingAs($kasir);

        \Livewire\Livewire::test(CashDrawerBook::class)->assertTableActionHidden('adjust_to_counted');
    }

    public function test_page_renders_for_owner(): void
    {
        CashBalance::updateOrCreate(['type' => 'CASHIER'], ['balance' => 0]);
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'owner'])->save();
        $this->actingAs($owner);

        \Livewire\Livewire::test(CashDrawerBook::class)
            ->assertSuccessful()
            ->assertTableColumnExists('running_balance');
    }
}
