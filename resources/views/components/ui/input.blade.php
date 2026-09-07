@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'prefix' => null,
])

{{-- Tahap 2 · Input berlabel. The control is 44 px tall so it stays a
     comfortable touch target on a phone. --}}
@php
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1 block text-xs font-medium text-muted">{{ $label }}</label>

    <div @class([
        'flex h-11 items-center gap-2 rounded-md border bg-surface px-3 transition-colors duration-150',
        'border-danger focus-within:ring-danger/20' => $hasError,
        'border-border focus-within:border-accent focus-within:ring-accent/20' => ! $hasError,
        'focus-within:ring-[3px]',
    ])>
        @if ($prefix)
            <span class="text-base text-muted">{{ $prefix }}</span>
        @endif

        <input id="{{ $name }}"
               name="{{ $name }}"
               type="{{ $type }}"
               value="{{ old($name, $value) }}"
               @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
               {{ $attributes->except('class')->merge([
                   'class' => 'min-w-0 flex-1 border-0 bg-transparent p-0 text-base text-fg outline-none placeholder:text-muted focus:ring-0',
               ]) }}>
    </div>

    @if ($hint && ! $hasError)
        <p class="mt-2 text-xs text-muted">{{ $hint }}</p>
    @endif

    {{-- The error sits next to its own field, not only at the top of the page. --}}
    @error($name)
        <p id="{{ $name }}-error" class="mt-2 text-xs text-danger-text">{{ $message }}</p>
    @enderror
</div>
