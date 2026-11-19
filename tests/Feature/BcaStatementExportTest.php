<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GeneralJournal;
use App\Models\User;
use App\Support\BcaStatementExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BcaStatementExportTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $payment, int $amount, string $at, string $status = 'COMPLETE'): void
    {
        static $n = 0;
        $customer = Customer::firstOrCreate(['phone_number' => '0811'], ['name' => 'Windy']);
        DB::table('orders')->insert([
            'order_number' => 'ORD' . (++$n), 'customer_id' => $customer->id, 'order_type' => 'SELF_PICKUP',
            'payment_type' => $payment, 'status' => $status, 'subtotal' => $amount, 'total_amount' => $amount,
            'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    private function cash(string $type, string $cashType, int $amount, string $desc, string $at): void
    {
        DB::table('cash_transactions')->insert([
            'type' => $type, 'cash_type' => $cashType, 'amount' => $amount, 'description' => $desc,
            'recorded_by' => User::factory()->create()->id, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    public function test_qris_export_lists_completed_qris_orders_in_bca_columns(): void
    {
        $this->order('QRIS', 99000, '2026-10-01 09:15:00');
        $this->order('QRIS', 6000, '2026-10-01 13:40:00');
        $this->order('QRIS', 5000, '2026-10-01 14:00:00', 'CANCELLED');
        $this->order('TUNAI', 20000, '2026-10-01 10:00:00');
        $this->order('QRIS', 7000, '2026-11-01 10:00:00');

        $rows = BcaStatementExport::rows('QRIS', Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame([99000, 6000], array_column($rows, 'amount'));
        $this->assertSame(['09:15', '13:40'], array_column($rows, 'time'));
        $this->assertSame(['CR', 'CR'], array_column($rows, 'direction'));
        $this->assertSame(105000, end($rows)['balance']);
        $this->assertStringContainsString('Windy', $rows[0]['description']);

        $csv = BcaStatementExport::toCsv($rows);
        $this->assertStringStartsWith("Tanggal;Jam;Keterangan;Jumlah;CR/DB;Saldo\n'01/10/2026;09:15;", $csv);
        $this->assertStringContainsString("Kredit;=;105000\n", $csv);
    }

    public function test_setoran_export_only_includes_deposits_to_main_cash(): void
    {
        $this->cash('DEPOSIT', 'CASHIER', 5250000, 'Setor ke kas besar atas nama Kasir A', '2026-10-05 20:00:00');
        $this->cash('DEPOSIT', 'CASHIER', 200000, 'Modal awal kasir', '2026-10-05 07:00:00');

        $rows = BcaStatementExport::rows('SETORAN', Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertCount(1, $rows);
        $this->assertSame(5250000, $rows[0]['amount']);
    }

    public function test_journal_tags_procurement_as_main_cash_not_tunai(): void
    {
        $this->cash('EXPENSE', 'MAIN', 100000, 'Pengadaan Stok #PR-1 (Disetujui Owner)', '2026-10-20 10:00:00');
        $this->order('QRIS', 9000, '2026-10-20 11:00:00');

        $procurement = GeneralJournal::where('source_type', 'CASH_TRX')->first();
        $this->assertSame('KAS_BESAR', $procurement->account);
        $this->assertSame('KAS BESAR', $procurement->payment_method);
        $this->assertSame('Belanja Stok', $procurement->category);
        $this->assertSame('QRIS', GeneralJournal::where('source_type', 'ORDER')->value('account'));
    }

    public function test_filament_export_action_downloads_csv(): void
    {
        $this->order('QRIS', 99000, now()->toDateTimeString());
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'owner'])->save();
        $this->actingAs($owner);

        \Livewire\Livewire::test(\App\Filament\Pages\GeneralJournal::class)
            ->assertTableColumnExists('account')
            ->callTableAction('export_bca', data: [
                'account' => 'QRIS', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString(),
            ])
            ->assertHasNoTableActionErrors()
            ->assertFileDownloaded('mutasi-qris-' . now()->startOfMonth()->format('Ymd') . '-' . now()->format('Ymd') . '.csv');
    }

    public function test_laci_export_has_running_balance_with_debits(): void
    {
        $this->order('TUNAI', 50000, '2026-10-02 08:00:00');
        $this->cash('DEPOSIT', 'CASHIER', 30000, 'Setor ke kas besar atas nama Kasir A', '2026-10-02 20:00:00');

        $rows = BcaStatementExport::rows('LACI', Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame(['CR', 'DB'], array_column($rows, 'direction'));
        $this->assertSame([50000, 20000], array_column($rows, 'balance'));
    }
}
