@php
    $antrian = $this->antrianCount();
    $baru = $this->antrianBaruMingguIni();
    $ujian = $this->ujianAkanDatang();
    $berikutnya = $this->ujianBerikutnya();
    $biaya = $this->biayaAiBulanIni();
    $batas = $this->batasBiayaAi();
    $lewatAnggaran = $batas !== null && $batas > 0 && $biaya > $batas;
    $daftar = $this->antrianList();
@endphp

<div class="space-y-6">
    <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Akademik · Dasbor</p>
        <h2 class="mt-1 text-xl font-semibold tracking-tight">
            Selamat mengajar, {{ auth()->user()->name }}
        </h2>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Soal menunggu tinjauan</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums tracking-tight">{{ number_format($antrian) }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $baru > 0 ? $baru.' soal baru minggu ini' : 'Tidak ada tambahan minggu ini' }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ujian akan datang</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums tracking-tight">{{ number_format($ujian) }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if ($berikutnya)
                    Terdekat: {{ $berikutnya->subject->name }},
                    {{ $berikutnya->starts_at->translatedFormat('d M, H:i') }}
                @else
                    Belum ada ujian terjadwal
                @endif
            </p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Biaya AI {{ $this->bulanIni() }}</p>
            <p @class([
                'mt-1 text-3xl font-semibold tabular-nums tracking-tight',
                'text-danger-600 dark:text-danger-400' => $lewatAnggaran,
            ])>{{ number_format($biaya, 2) }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if ($batas !== null && $batas > 0)
                    dari anggaran {{ number_format($batas, 2) }}{{ $lewatAnggaran ? ' — sudah terlampaui' : '' }}
                @else
                    Anggaran bulanan belum diatur
                @endif
            </p>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Antrian tinjauan</x-slot>

        <x-slot name="description">
            Soal hasil AI selalu masuk antrian ini dulu. Tidak ada soal yang terbit tanpa
            persetujuan guru.
        </x-slot>

        @if ($daftar->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Tidak ada soal yang menunggu ditinjau. Antrian bersih.
            </p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($daftar as $soal)
                    <li class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $soal->stem }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $soal->subject?->name ?? 'Tanpa mata pelajaran' }} ·
                                {{ $soal->source->label() }} ·
                                {{ $soal->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4">
                <x-filament::link :href="\App\Filament\Resources\Questions\QuestionResource::getUrl('index')">
                    Buka Bank Soal
                </x-filament::link>
            </div>
        @endif
    </x-filament::section>
</div>
