@props(['label' => null])

{{-- Tahap 2 · Tab pil. A segmented control, one active at a time. --}}
<div {{ $attributes->merge([
    'class' => 'inline-flex gap-1 rounded-full bg-surface-muted p-1',
]) }}
     role="tablist"
     @if ($label) aria-label="{{ $label }}" @endif>
    {{ $slot }}
</div>
