@props([
    'items' => [],
    'current' => 0,
])

{{--
    Tahap 2 · Peta nomor soal.

    Answered cells read as answered through fill AND the legend below, so the
    student is not asked to remember what a colour means.
--}}
<div {{ $attributes }}>
    <p class="mb-2 text-xs font-medium uppercase tracking-[0.06em] text-muted">Daftar soal</p>

    <ul class="grid grid-cols-5 gap-2 sm:grid-cols-8">
        @foreach ($items as $index => $item)
            <li>
                <button type="button"
                        wire:click="ke({{ $index }})"
                        aria-label="Ke soal nomor {{ $item['number'] }}{{ $item['answered'] ? ', sudah dijawab' : ', belum dijawab' }}"
                        @if ($index === $current) aria-current="true" @endif
                        @class([
                            'flex aspect-square w-full items-center justify-center rounded-md text-sm font-semibold transition-colors duration-150',
                            'bg-accent text-accent-fg' => $index === $current,
                            'bg-success-soft text-success-text' => $index !== $current && $item['answered'],
                            'bg-surface-muted text-fg hover:bg-neutral-soft' => $index !== $current && ! $item['answered'],
                        ])>
                    {{ $item['number'] }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="mt-3 flex flex-wrap gap-3 text-xs text-muted">
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm bg-accent"></span> Soal ini
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm bg-success-soft ring-1 ring-success"></span> Sudah dijawab
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm bg-surface-muted ring-1 ring-border"></span> Belum dijawab
        </span>
    </div>
</div>
