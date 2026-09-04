<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-4 sm:p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <p class="text-sm text-slate-600">
                Levelmu sekarang:
                <strong class="text-base text-slate-900">{{ $level }}</strong>
            </p>

            <p class="text-sm text-slate-600">
                Dijawab {{ $dijawab }} &middot; benar {{ $benar }}
            </p>
        </div>

        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-slate-900 transition-all duration-500"
                 style="width: {{ $progres }}%"></div>
        </div>

        <p class="mt-2 text-sm text-slate-600">{{ $levelKeterangan }}</p>
    </div>

    @if ($selesai)
        <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
            <h1 class="text-xl font-semibold text-slate-900">Latihan selesai</h1>
            <p class="mt-2 text-base text-slate-700">
                Kamu menjawab {{ $dijawab }} soal, {{ $benar }} di antaranya benar.
            </p>
            <p class="mt-1 text-sm text-slate-600">
                Latihan tidak mempengaruhi peringkat. Yang dihitung untuk peringkat hanya ujian.
            </p>

            <a href="{{ route('latihan.index') }}" wire:navigate
               class="mt-6 inline-block min-h-11 rounded bg-slate-900 px-5 py-2.5 text-base font-medium text-white hover:bg-slate-800">
                Kembali ke daftar mata pelajaran
            </a>
        </div>
    @elseif ($habis || $soal === null)
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h1 class="text-lg font-semibold text-slate-900">Belum ada soal untuk dilatih</h1>
            <p class="mt-2 text-base text-slate-700">
                Mata pelajaran ini belum punya soal yang bisa dilatih. Beri tahu gurumu, ya.
            </p>

            <a href="{{ route('latihan.index') }}" wire:navigate
               class="mt-5 inline-block min-h-11 rounded border border-slate-300 bg-white px-5 py-2.5 text-base font-medium text-slate-900">
                Kembali
            </a>
        </div>
    @else
        <x-question-body
            :number="$dijawab + 1"
            :stem="$soal['stem']"
            :options="$soal['options']"
            :selected="$pilihan"
            :highlight="$umpanBalik['kunci'] ?? null"
            :name="'latihan-'.$soal['id']"
            :interactive="$umpanBalik === null"
            wire:model="pilihan"
            wire:key="soal-{{ $soal['id'] }}"
        />

        @error('pilihan')
            <p class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ $message }}</p>
        @enderror

        @if ($umpanBalik === null)
            <button type="button" wire:click="jawab" @disabled($pilihan === null)
                    class="min-h-11 w-full rounded bg-slate-900 px-4 py-2.5 text-base font-medium text-white
                           hover:bg-slate-800 disabled:opacity-40">
                Periksa jawaban
            </button>
        @else
            <div @class([
                'rounded-lg border p-4',
                'border-emerald-300 bg-emerald-50' => $umpanBalik['benar'],
                'border-amber-300 bg-amber-50' => ! $umpanBalik['benar'],
            ])>
                <p class="text-base font-semibold @if ($umpanBalik['benar']) text-emerald-900 @else text-amber-900 @endif">
                    @if ($umpanBalik['benar'])
                        Benar.
                    @else
                        Belum tepat. Jawaban yang benar: {{ $umpanBalik['kunci'] }}.
                    @endif
                </p>

                @if ($umpanBalik['pembahasan'])
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed
                              @if ($umpanBalik['benar']) text-emerald-900 @else text-amber-900 @endif">
                        {{ $umpanBalik['pembahasan'] }}
                    </p>
                @endif

                @if ($umpanBalik['naikLevel'])
                    <p class="mt-3 rounded bg-white/70 p-2 text-sm font-medium text-slate-900">
                        Levelmu berubah menjadi {{ $level }}.
                    </p>
                @endif
            </div>

            <button type="button" wire:click="berikutnya"
                    class="min-h-11 w-full rounded bg-slate-900 px-4 py-2.5 text-base font-medium text-white hover:bg-slate-800">
                Soal berikutnya
            </button>
        @endif

        <button type="button" wire:click="akhiri"
                class="min-h-11 w-full rounded border border-slate-300 bg-white px-4 py-2.5 text-base font-medium text-slate-900">
            Sudahi latihan
        </button>
    @endif
</div>
