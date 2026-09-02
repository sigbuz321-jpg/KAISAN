@php
    $rows = $this->rows();
    $budget = $this->monthlyBudget();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Bulan berjalan</x-slot>

            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="text-3xl font-semibold tracking-tight">
                    {{ number_format($this->currentMonthCost(), 2) }}
                </span>

                @if ($budget !== null && $budget > 0)
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        dari anggaran {{ number_format($budget, 2) }}
                    </span>
                @endif
            </div>

            @if ($this->overBudget())
                <p class="mt-3 rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">
                    Pemakaian bulan ini sudah melewati anggaran yang disetel.
                    Pertimbangkan mengurangi jumlah soal per permintaan, atau menaikkan
                    anggaran di berkas <code>.env</code>.
                </p>
            @endif

            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                Angka ini perkiraan yang dihitung dari jumlah token. Tagihan resmi dari
                penyedia AI tetap menjadi acuan.
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Riwayat per bulan</x-slot>

            @if (blank($rows))
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada permintaan soal AI yang selesai.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-200 text-left dark:border-white/10">
                            <tr class="text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4 font-medium">Bulan</th>
                                <th class="py-2 pr-4 text-right font-medium">Permintaan</th>
                                <th class="py-2 pr-4 text-right font-medium">Soal tersimpan</th>
                                <th class="py-2 pr-4 text-right font-medium">Token</th>
                                <th class="py-2 text-right font-medium">Perkiraan biaya</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="py-2 pr-4">{{ $row['month'] }}</td>
                                    <td class="py-2 pr-4 text-right">{{ number_format($row['jobs']) }}</td>
                                    <td class="py-2 pr-4 text-right">{{ number_format($row['questions']) }}</td>
                                    <td class="py-2 pr-4 text-right">{{ number_format($row['tokens']) }}</td>
                                    <td class="py-2 text-right font-medium">{{ number_format((float) $row['cost'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
