@props([
    'number' => 1,
    'total' => null,
    'stem',
    'options',
    'name' => null,
    'selected' => null,
    'highlight' => null,
    'interactive' => false,
    'subject' => null,
])

{{--
    Tahap 2 · Kartu soal.

    One rendering of a multiple-choice question, shared by the teacher's
    preview and the live exam screen so the two cannot drift apart.

    It takes plain data -- a stem and a letter => text array -- and never a
    Question model, so there is no answer_key in scope to leak by accident.
    `highlight` is set only on the teacher's side and after practice feedback.
--}}
@php
    $group = $name ?? 'soal-'.$number;

    // Anything the caller bound (wire:model, wire:key) belongs on the radio,
    // not on the article wrapper.
    $bind = $attributes->whereStartsWith('wire:');
    $rest = $attributes->whereDoesntStartWith('wire:');
@endphp

<article {{ $rest->merge([
    'class' => 'rounded-xl border border-border bg-surface p-5 sm:p-6',
]) }}>
    <div class="mb-4 flex items-center gap-2">
        @if ($subject)
            <x-ui.badge tone="accent">{{ $subject }}</x-ui.badge>
        @endif

        <p class="ms-auto text-xs font-medium tracking-[0.02em] text-muted">
            Soal {{ $number }}@if ($total) dari {{ $total }}@endif
        </p>
    </div>

    <p class="mb-5 whitespace-pre-line text-lg leading-relaxed text-fg">{{ $stem }}</p>

    <div class="space-y-2">
        @foreach ($options as $letter => $text)
            <x-ui.answer-option
                :letter="$letter"
                :text="$text"
                :name="$group"
                :selected="$letter === $selected"
                :state="$highlight === null
                    ? null
                    : ($letter === $highlight ? 'correct' : ($letter === $selected ? 'wrong' : null))"
                :interactive="$interactive"
                :bind="$interactive ? $bind : null"
            />
        @endforeach
    </div>

    {{ $slot }}
</article>
