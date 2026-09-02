@php
    $soal = $paper[$current] ?? null;
    $jumlah = count($paper);
@endphp

<div class="space-y-6">
    @if ($selesai)
        <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
            @if ($skor !== null)
                <h1 class="text-xl font-semibold text-slate-900">Ujian sudah dikumpulkan</h1>
                <p class="mt-2 text-sm text-slate-600">Nilai kamu:</p>
                <p class="mt-1 text-4xl font-bold text-slate-900">{{ rtrim(rtrim($skor, '0'), '.') }}</p>
            @else
                <h1 class="text-xl font-semibold text-slate-900">Ujian selesai</h1>
                <p class="mt-3 text-base text-slate-700">{{ $pesan }}</p>
            @endif

            <a href="{{ route('ujian.index') }}" wire:navigate
               class="mt-6 inline-block min-h-11 rounded bg-slate-900 px-5 py-2.5 text-base font-medium text-white hover:bg-slate-800">
                Kembali ke daftar ujian
            </a>
        </div>
    @else
        {{--
            The countdown runs in the browser so 150 students do not poll the
            server once a second. It measures elapsed time since the page
            loaded rather than reading the wall clock, so changing the device
            clock does nothing. It is a display: the server decides whether a
            submission is on time, and grades an abandoned attempt on its own.
        --}}
        <div x-data="{
                sisa: Math.max(0, {{ $deadlineAt - $serverNow }}),
                init() {
                    const total = this.sisa;
                    const mulai = Date.now();
                    const id = setInterval(() => {
                        this.sisa = Math.max(0, total - Math.floor((Date.now() - mulai) / 1000));
                        if (this.sisa === 0) {
                            clearInterval(id);
                            $wire.kumpulkan();
                        }
                    }, 1000);
                },
                get jam() {
                    const m = String(Math.floor(this.sisa / 60)).padStart(2, '0');
                    const d = String(this.sisa % 60).padStart(2, '0');
                    return m + ':' + d;
                },
             }"
             class="sticky top-0 z-10 -mx-4 flex items-center justify-between gap-4 border-b border-slate-200
                    bg-white/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-lg sm:border">
            <p class="text-sm text-slate-600">
                Soal <span class="font-semibold text-slate-900">{{ $current + 1 }}</span> dari {{ $jumlah }}
            </p>

            <p class="flex items-baseline gap-2">
                <span class="text-sm text-slate-600">Sisa waktu</span>
                <span x-text="jam"
                      :class="sisa <= 60 ? 'text-red-600' : 'text-slate-900'"
                      class="font-mono text-lg font-semibold tabular-nums">--:--</span>
            </p>
        </div>

        @if ($pesan)
            <p class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ $pesan }}</p>
        @endif

        @if ($soal)
            <x-question-body
                :number="$soal['number']"
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
            <button type="button" wire:click="ke({{ $current - 1 }})" @disabled($current === 0)
                    class="min-h-11 rounded border border-slate-300 bg-white px-4 py-2.5 text-base font-medium
                           text-slate-900 hover:border-slate-400 disabled:opacity-40">
                Sebelumnya
            </button>

            <button type="button" wire:click="ke({{ $current + 1 }})" @disabled($current >= $jumlah - 1)
                    class="min-h-11 rounded border border-slate-300 bg-white px-4 py-2.5 text-base font-medium
                           text-slate-900 hover:border-slate-400 disabled:opacity-40">
                Berikutnya
            </button>
        </div>

        <div>
            <p class="mb-2 text-sm font-medium text-slate-700">Daftar soal</p>

            <ul class="flex flex-wrap gap-2">
                @foreach ($paper as $i => $item)
                    <li>
                        <button type="button" wire:click="ke({{ $i }})"
                                aria-label="Ke soal nomor {{ $item['number'] }}"
                                @class([
                                    'flex h-11 w-11 items-center justify-center rounded border text-sm font-medium',
                                    'border-slate-900 bg-slate-900 text-white' => $i === $current,
                                    'border-emerald-400 bg-emerald-50 text-emerald-900'
                                        => $i !== $current && ($answers[$item['id']] ?? null) !== null,
                                    'border-slate-300 bg-white text-slate-700'
                                        => $i !== $current && ($answers[$item['id']] ?? null) === null,
                                ])>
                            {{ $item['number'] }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <p class="mt-2 text-sm text-slate-600">
                Jawaban tersimpan otomatis setiap kali kamu memilih. Kotak hijau berarti sudah dijawab.
            </p>
        </div>

        <div x-data="{ konfirmasi: false }" class="border-t border-slate-200 pt-6">
            <template x-if="! konfirmasi">
                <button type="button" @click="konfirmasi = true"
                        class="min-h-11 w-full rounded bg-slate-900 px-4 py-2.5 text-base font-medium text-white hover:bg-slate-800">
                    Kumpulkan ujian
                </button>
            </template>

            <template x-if="konfirmasi">
                <div class="rounded border border-slate-300 bg-white p-4">
                    <p class="text-base text-slate-900">
                        @if ($this->belumDijawab() > 0)
                            Masih ada <strong>{{ $this->belumDijawab() }}</strong> soal yang belum dijawab.
                            Soal yang kosong dihitung salah.
                        @else
                            Semua soal sudah dijawab.
                        @endif
                    </p>

                    <p class="mt-1 text-sm text-slate-600">Setelah dikumpulkan, jawaban tidak bisa diubah lagi.</p>

                    <div class="mt-4 flex gap-3">
                        <button type="button" wire:click="kumpulkan" wire:loading.attr="disabled"
                                class="min-h-11 flex-1 rounded bg-slate-900 px-4 py-2.5 text-base font-medium text-white hover:bg-slate-800">
                            Ya, kumpulkan
                        </button>

                        <button type="button" @click="konfirmasi = false"
                                class="min-h-11 flex-1 rounded border border-slate-300 bg-white px-4 py-2.5 text-base font-medium text-slate-900">
                            Batal
                        </button>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
