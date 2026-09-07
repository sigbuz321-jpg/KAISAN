{{--
    Tahap 2 · Indikator simpan otomatis.

    Students on unstable connections need to see that an answer landed. Driven
    by Livewire's own request lifecycle, so it never lies about the state.
--}}
<div {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs transition-opacity duration-200',
]) }}
     aria-live="polite">
    <span wire:loading.remove wire:target="answers" class="inline-flex items-center gap-2 text-success-text">
        <span class="h-2 w-2 rounded-full bg-success"></span>
        Tersimpan
    </span>

    <span wire:loading wire:target="answers" class="inline-flex items-center gap-2 text-warning-text">
        <span class="h-2 w-2 rounded-full bg-warning"></span>
        Menyimpan...
    </span>
</div>
