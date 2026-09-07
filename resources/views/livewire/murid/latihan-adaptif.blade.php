@php
    // $level arrives as a label string from the component; map it back to a
    // tone so the band reads the same here as it does on the index screen.
    $levelTone = match (strtolower($level)) {
        'pemula' => 'info',
        'berkembang' => 'warning',
        'mahir' => 'success',
        'ahli' => 'accent',
        default => 'neutral',
    };
@endphp

<div class="space-y-5">
    {{-- React Micro-Island for Motion.dev spring celebrations, confetti burst, and SFX --}}
    <div data-react-island="latihan-feedback-overlay"></div>

    @if (! $selesai)
        <section class="rounded-lg border border-border bg-surface p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium uppercase tracking-[0.06em] text-muted">Levelmu</span>
                    <x-ui.badge :tone="$levelTone">{{ $level }}</x-ui.badge>
                </div>

                <p class="text-sm text-muted">
                    Dijawab {{ $dijawab }} &middot; benar {{ $benar }}
                </p>
            </div>

            <x-ui.progress :value="$progres" class="mt-4" />

            <p class="mt-3 text-sm leading-relaxed text-muted">{{ $levelKeterangan }}</p>
        </section>
    @endif

    @if ($selesai)
        {{-- A satisfying close, not a hook: the primary action ends the
             session rather than baiting another one. --}}
        <section class="rounded-lg border border-border bg-surface p-6 text-center">
            <h1 class="text-[1.375rem] font-semibold tracking-[-0.01em] text-fg">Latihan selesai</h1>

            <p class="mt-3 text-base leading-relaxed text-fg">
                Kamu menjawab {{ $dijawab }} soal, {{ $benar }} di antaranya benar.
            </p>

            <div class="mt-4 flex justify-center">
                <x-ui.badge :tone="$levelTone">Level {{ $level }}</x-ui.badge>
            </div>

            <p class="mt-4 text-sm leading-relaxed text-muted">
                Latihan tidak menambah poin peringkat. Yang dihitung untuk peringkat hanya ujian.
            </p>

            <div class="mt-6">
                <x-ui.button :href="route('latihan.index')" :block="false">Selesai</x-ui.button>
            </div>
        </section>
    @elseif ($habis || $soal === null)
        <x-ui.empty title="Belum ada soal untuk dilatih">
            Mata pelajaran ini belum punya soal yang bisa dilatih. Coba tanyakan ke gurumu.

            <x-slot:action>
                <x-ui.button :href="route('latihan.index')" variant="secondary" :block="false">
                    Kembali
                </x-ui.button>
            </x-slot:action>
        </x-ui.empty>
    @else
        <x-ui.question-card
            :number="$dijawab + 1"
            :stem="$soal['stem']"
            :options="$soal['options']"
            :selected="$pilihan"
            :highlight="$umpanBalik['kunci'] ?? null"
            :name="'latihan-'.$soal['id']"
            :interactive="$umpanBalik === null"
            {{-- .live, not deferred: the radio is sr-only and the selected
                 styling comes from server state, so a deferred bind would leave
                 the choice invisible and the check button disabled forever. --}}
            wire:model.live="pilihan"
            wire:key="soal-{{ $soal['id'] }}"
        />

        @error('pilihan')
            <x-ui.banner tone="warning">{{ $message }}</x-ui.banner>
        @enderror

        @if ($umpanBalik === null)
            <x-ui.button wire:click="jawab" type="button" :disabled="$pilihan === null">
                Periksa jawaban
            </x-ui.button>
        @else
            <x-ui.banner :tone="$umpanBalik['benar'] ? 'success' : 'danger'"
                         :title="$umpanBalik['benar'] ? 'Benar.' : 'Belum tepat. Jawaban yang benar: '.$umpanBalik['kunci'].'.'">
                @if ($umpanBalik['pembahasan'])
                    <p class="whitespace-pre-line leading-relaxed">{{ $umpanBalik['pembahasan'] }}</p>
                @endif

                @if ($umpanBalik['naikLevel'])
                    <p class="mt-3 font-medium">Levelmu berubah menjadi {{ $level }}.</p>
                @endif

                @unless ($umpanBalik['benar'])
                    {{-- Point 3 of 3: the contract line also lands right where a
                         student is most likely to feel a wrong answer costs them. --}}
                    <p class="mt-3">Tenang, latihan tidak menambah atau mengurangi poin peringkat.</p>
                @endunless
            </x-ui.banner>

            <x-ui.button wire:click="berikutnya" type="button">Soal berikutnya</x-ui.button>
        @endif

        <x-ui.button wire:click="akhiri" type="button" variant="secondary">Sudahi latihan</x-ui.button>
    @endif
</div>
