@php
    $soal = $paper[$current] ?? null;
    $jumlah = count($paper);

    $peta = collect($paper)->map(fn (array $item) => [
        'number' => $item['number'],
        'answered' => ($answers[$item['id']] ?? null) !== null,
    ])->all();

    $nilaiAngka = is_string($skor) ? (float) $skor : ($skor ?? 0);
@endphp

<div class="space-y-5">
    @if ($selesai)
        <section class="rounded-lg border border-border bg-surface p-6 text-center">
            @if ($skor !== null)
                <h1 class="text-[1.375rem] font-semibold tracking-[-0.01em] text-fg">Ujian sudah dikumpulkan</h1>

                {{-- Exam Celebration Island: counter roll-up + confetti + chord --}}
                <div data-react-island="exam-celebration" data-react-props="@json(['score' => $nilaiAngka])"></div>
            @else
                <h1 class="text-[1.375rem] font-semibold tracking-[-0.01em] text-fg">Ujian selesai</h1>
                <p class="mt-3 text-base leading-relaxed text-fg">{{ $pesan }}</p>
            @endif

            <div class="mt-6">
                <x-ui.button :href="route('ujian.index')" :block="false">Kembali ke daftar ujian</x-ui.button>
            </div>
        </section>
    @else
        {{--
            Exam header: position, remaining time, and whether the last answer
            landed. Sticky because a student scrolling a long question still
            needs to see the clock.
        --}}
        <div class="sticky top-14 z-10 -mx-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2
                    border-b border-border bg-surface/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-lg sm:border">
            <p class="text-sm text-muted">
                Soal <span class="font-semibold text-fg">{{ $current + 1 }}</span> dari {{ $jumlah }}
            </p>

            <div class="flex items-center gap-2">
                <x-ui.save-indicator />
                <x-ui.timer :seconds="$deadlineAt - $serverNow" />
            </div>
        </div>

        @if ($pesan)
            <x-ui.banner tone="warning">{{ $pesan }}</x-ui.banner>
        @endif

        @if ($soal)
            <x-ui.question-card
                :number="$soal['number']"
                :total="$jumlah"
                :stem="$soal['stem']"
                :options="$soal['options']"
                :selected="$answers[$soal['id']] ?? null"
                :name="'soal-'.$soal['id']"
                interactive
                wire:model.live="answers.{{ $soal['id'] }}"
                wire:key="soal-{{ $soal['id'] }}"
            />
        @endif

        <div class="flex items-center justify-between gap-3">
            <x-ui.button wire:click="ke({{ $current - 1 }})" type="button" variant="secondary"
                         :block="false" :disabled="$current === 0">
                Sebelumnya
            </x-ui.button>

            <x-ui.button wire:click="ke({{ $current + 1 }})" type="button" variant="secondary"
                         :block="false" :disabled="$current >= $jumlah - 1">
                Berikutnya
            </x-ui.button>
        </div>

        <x-ui.question-map :items="$peta" :current="$current" />

        <p class="text-sm text-muted">
            Jawaban tersimpan otomatis setiap kali kamu memilih.
        </p>

        <div x-data="{ konfirmasi: false }" class="border-t border-border pt-5">
            <template x-if="! konfirmasi">
                <div>
                    <x-ui.button x-on:click="konfirmasi = true" type="button">Kumpulkan ujian</x-ui.button>
                </div>
            </template>

            <template x-if="konfirmasi">
                <div class="rounded-lg border border-border bg-surface p-4">
                    {{-- Neutral about unanswered questions: state the number,
                         do not scold. --}}
                    <p class="text-base text-fg">
                        @if ($this->belumDijawab() > 0)
                            {{ $this->belumDijawab() }} soal belum dijawab. Soal yang kosong dihitung salah.
                        @else
                            Semua soal sudah dijawab.
                        @endif
                    </p>

                    <p class="mt-1 text-sm text-muted">Setelah dikumpulkan, jawaban tidak bisa diubah lagi.</p>

                    <div class="mt-4 flex gap-3">
                        <x-ui.button wire:click="kumpulkan" wire:loading.attr="disabled" type="button" class="flex-1">
                            Ya, kumpulkan
                        </x-ui.button>

                        <x-ui.button x-on:click="konfirmasi = false" type="button" variant="secondary" class="flex-1">
                            Kembali mengerjakan
                        </x-ui.button>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
