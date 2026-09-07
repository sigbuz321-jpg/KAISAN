@props([
    'subject',
    'ability' => null,
    'href' => null,
    'available' => true,
])

{{--
    Tahap 3 · Kartu mata pelajaran di daftar latihan.

    A subject with no published questions is still listed but not offered:
    better to say the bank is empty than to hand a student a screen that cannot
    give them anything.
--}}
@php
    $glyph = mb_strtoupper(mb_substr($subject->name, 0, 1));
@endphp

<li class="rounded-lg border border-border bg-surface p-4 sm:p-5 {{ $available ? '' : 'opacity-70' }}">
    <div class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md font-bold {{ $available ? 'bg-accent-soft text-accent-text' : 'bg-surface-muted text-muted' }}"
              aria-hidden="true">{{ $glyph }}</span>

        <div class="min-w-0 flex-1">
            <h3 class="text-base font-semibold leading-snug text-fg">{{ $subject->name }}</h3>

            @if ($ability)
                <p class="mt-0.5 text-xs text-muted">
                    {{ $ability->answers_count }} soal sudah kamu kerjakan
                </p>
            @endif
        </div>

        <x-ui.badge-level :level="$ability?->level()" class="shrink-0" />
    </div>

    @if ($ability)
        <x-ui.progress :value="$ability->progress()"
                       :label="'Progres di level '.$ability->level()->label()"
                       class="mt-4" />
    @endif

    <div class="mt-5">
        @if ($available && $href)
            <x-ui.button :href="$href" :block="false">
                {{ $ability ? 'Lanjut berlatih' : 'Mulai berlatih' }}
            </x-ui.button>
        @else
            <p class="text-sm text-muted">
                Belum ada soal di mata pelajaran ini. Coba tanyakan ke gurumu.
            </p>
        @endif
    </div>
</li>
