@props([
    'letter',
    'text',
    'name',
    'selected' => false,
    'state' => null,
    'interactive' => true,
    'bind' => null,
])

{{--
    Tahap 2 · Opsi jawaban A-D.

    `state` is 'correct' or 'wrong' and is only ever set after an answer has
    been checked -- during an exam nothing here knows the key.

    Correctness is shown with a letter chip and words as well as colour, so the
    meaning survives for a colour-blind student.

    `bind` carries the caller's wire:model so the radio stays the bound control.
--}}
@php
    $shell = match (true) {
        $state === 'correct' => 'border-success bg-success-soft',
        $state === 'wrong' => 'border-danger bg-danger-soft',
        $selected => 'border-accent bg-accent-soft',
        default => 'border-border bg-surface',
    };

    $chip = match (true) {
        $state === 'correct' => 'bg-success text-success-fg',
        $state === 'wrong' => 'bg-danger text-danger-fg',
        $selected => 'bg-accent text-accent-fg',
        default => 'bg-surface-muted text-fg',
    };
@endphp

<label @class([
    'flex w-full items-center gap-3 rounded-md border px-4 py-3 text-base text-fg transition-colors duration-150',
    'cursor-pointer hover:border-border-strong focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent' => $interactive,
    'cursor-default opacity-60' => ! $interactive,
    $shell,
])>
    <input type="radio"
           name="{{ $name }}"
           value="{{ $letter }}"
           class="sr-only"
           @checked($selected)
           @disabled(! $interactive)
           {{ $bind ?? '' }}
           {{ $attributes }}>

    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-sm text-sm font-semibold {{ $chip }}">
        {{ $letter }}
    </span>

    <span class="min-w-0 flex-1">{{ $text }}</span>

    @if ($state === 'correct')
        <x-icon.check class="h-5 w-5 shrink-0 text-success" />
        <span class="sr-only">Jawaban benar</span>
    @elseif ($state === 'wrong')
        <x-icon.x-mark class="h-5 w-5 shrink-0 text-danger" />
        <span class="sr-only">Jawaban yang kamu pilih, salah</span>
    @else
        <span @class([
            'h-5 w-5 shrink-0 rounded-full border',
            'border-accent bg-accent' => $selected,
            'border-border' => ! $selected,
        ])></span>
    @endif
</label>
