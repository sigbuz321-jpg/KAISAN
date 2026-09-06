@props(['title' => null])

{{-- Tahap 2 · Empty state. Neutral in tone: an empty screen is not an error,
     and a student cannot fix it themselves. --}}
<div {{ $attributes->merge([
    'class' => 'rounded-lg border border-border bg-surface-muted p-6 text-center',
]) }}>
    @if ($title)
        <p class="text-base font-semibold text-fg">{{ $title }}</p>
    @endif

    <div class="{{ $title ? 'mt-2 ' : '' }}text-sm leading-relaxed text-muted">
        {{ $slot }}
    </div>

    @isset($action)
        <div class="mt-5 flex justify-center">{{ $action }}</div>
    @endisset
</div>
