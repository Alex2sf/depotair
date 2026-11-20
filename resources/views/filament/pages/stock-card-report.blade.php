<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <label class="text-sm">
                <span class="block mb-1 text-gray-600 dark:text-gray-300">Dari tanggal</span>
                <input type="date" wire:model.live="from" class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            </label>
            <label class="text-sm">
                <span class="block mb-1 text-gray-600 dark:text-gray-300">Sampai tanggal</span>
                <input type="date" wire:model.live="to" class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md">
                Stok akhir = stok awal + restock + retur − jual − rusak ± penyesuaian.
                Selisih opname = hitungan fisik − stok sistem saat dihitung.
            </p>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-2 pr-3">Produk</th>
                        <th class="py-2 px-2 text-right">Stok awal</th>
                        <th class="py-2 px-2 text-right">+ Restock</th>
                        <th class="py-2 px-2 text-right">+ Retur</th>
                        <th class="py-2 px-2 text-right">− Jual</th>
                        <th class="py-2 px-2 text-right">− Rusak</th>
                        <th class="py-2 px-2 text-right">± Sesuai</th>
                        <th class="py-2 px-2 text-right">Stok akhir</th>
                        <th class="py-2 px-2 text-right">Opname terakhir</th>
                        <th class="py-2 pl-2 text-right">Selisih opname</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $row)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-3 font-medium">{{ $row['product'] }} <span class="text-xs text-gray-400">{{ $row['unit'] }}</span></td>
                            <td class="py-2 px-2 text-right">{{ $row['opening'] }}</td>
                            <td class="py-2 px-2 text-right text-success-600">{{ $row['restock'] ?: '-' }}</td>
                            <td class="py-2 px-2 text-right text-success-600">{{ $row['return'] ?: '-' }}</td>
                            <td class="py-2 px-2 text-right text-danger-600">{{ $row['sale'] ?: '-' }}</td>
                            <td class="py-2 px-2 text-right text-danger-600">{{ $row['damage'] ?: '-' }}</td>
                            <td class="py-2 px-2 text-right">{{ $row['adjustment'] ?: '-' }}</td>
                            <td class="py-2 px-2 text-right font-semibold">
                                {{ $row['closing'] }}
                                @if ($row['closing'] !== $row['current'] && $row['current'] !== null && \Carbon\Carbon::parse($this->to)->isToday())
                                    <span class="block text-xs text-warning-600" title="Stok di tabel inventori berbeda dari hasil hitung riwayat gerakan">sistem: {{ $row['current'] }}</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right">
                                @if ($row['opname_at'])
                                    {{ $row['opname_real'] }}
                                    <span class="block text-xs text-gray-400">{{ $row['opname_at']->format('d/m H:i') }} · sistem {{ $row['opname_system'] }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-2 pl-2 text-right font-semibold">
                                @if ($row['opname_at'] === null)
                                    <span class="text-gray-400">-</span>
                                @elseif ($row['opname_difference'] === 0)
                                    <span class="text-success-600">Pas</span>
                                @else
                                    <span class="{{ $row['opname_difference'] < 0 ? 'text-danger-600' : 'text-warning-600' }}">
                                        {{ $row['opname_difference'] > 0 ? '+' : '' }}{{ $row['opname_difference'] }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-6 text-center text-gray-400">Belum ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
