@php
    $stats = $this->stats();
    $attempts = $this->attempts();
    $tersulit = $this->hardestQuestions();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Ringkasan</x-slot>

            <dl class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                @foreach ([
                    'Mengerjakan' => $stats['peserta'],
                    'Sudah dinilai' => $stats['dinilai'],
                    'Rata-rata' => $stats['rata'] ?? '-',
                    'Tertinggi' => $stats['tertinggi'] ?? '-',
                    'Terendah' => $stats['terendah'] ?? '-',
                ] as $label => $value)
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($record->status !== \App\Enums\ExamStatus::Graded)
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    Ujian belum selesai dinilai. Nilai akhir muncul otomatis setelah ujian ditutup,
                    termasuk untuk murid yang koneksinya terputus sebelum sempat mengumpulkan.
                </p>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Nilai per murid</x-slot>

            @if ($attempts->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada murid yang mengerjakan ujian ini.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-200 text-left dark:border-white/10">
                            <tr class="text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4 font-medium">Murid</th>
                                <th class="py-2 pr-4 font-medium">Kelas</th>
                                <th class="py-2 pr-4 text-right font-medium">Benar</th>
                                <th class="py-2 pr-4 text-right font-medium">Nilai</th>
                                <th class="py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($attempts as $attempt)
                                <tr>
                                    <td class="py-2 pr-4">{{ $attempt->student->name }}</td>
                                    <td class="py-2 pr-4">{{ $attempt->student->classroom?->name ?? '-' }}</td>
                                    <td class="py-2 pr-4 text-right">
                                        @if ($attempt->correct_count !== null)
                                            {{ $attempt->correct_count }} / {{ $attempt->total_questions }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-right font-medium">
                                        {{ $attempt->score !== null ? number_format((float) $attempt->score, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-success-100 text-success-800' => $attempt->status === \App\Enums\AttemptStatus::Submitted,
                                            'bg-warning-100 text-warning-800' => $attempt->status === \App\Enums\AttemptStatus::InProgress,
                                            'bg-danger-100 text-danger-800' => $attempt->status === \App\Enums\AttemptStatus::Voided,
                                        ])>
                                            {{ $attempt->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @endif
        </x-filament::section>

        @php $absen = $this->absentStudents(); @endphp

        <x-filament::section>
            <x-slot name="heading">Belum mengerjakan</x-slot>

            @if ($absen->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Semua murid di kelas peserta sudah membuka ujian ini.
                </p>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach ($absen as $murid)
                        <li class="rounded-full bg-gray-100 px-3 py-1 text-sm dark:bg-white/5">
                            {{ $murid->name }}
                            <span class="text-gray-500 dark:text-gray-400">
                                &middot; {{ $murid->classroom?->name ?? '-' }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    Mereka tidak dicatat bernilai nol. Tidak hadir dan mendapat nol adalah dua hal
                    berbeda saat dijelaskan ke orang tua.
                </p>
            @endif
        </x-filament::section>

        @if (filled($tersulit))
            <x-filament::section>
                <x-slot name="heading">Soal yang paling banyak dijawab salah</x-slot>

                <ul class="space-y-3">
                    @foreach ($tersulit as $soal)
                        <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1
                                   border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-white/5">
                            <span class="max-w-2xl">{{ \Illuminate\Support\Str::limit($soal['stem'], 120) }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $soal['benar'] }} dari {{ $soal['dijawab'] }} benar
                                <strong class="ms-1 text-gray-900 dark:text-white">({{ $soal['persen'] }}%)</strong>
                            </span>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    Soal dengan persentase rendah biasanya menandakan materi yang perlu diulang.
                </p>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
