@props(['active' => null])

{{-- The same four destinations as the bottom tab bar, laid out for a wider
     screen. Kept in one place so the two navigations cannot disagree. --}}
@php
    $tabs = [
        ['key' => 'latihan', 'label' => 'Latihan', 'route' => 'latihan.index'],
        ['key' => 'ujian', 'label' => 'Ujian', 'route' => 'ujian.index'],
        ['key' => 'peringkat', 'label' => 'Peringkat', 'route' => 'peringkat.index'],
        ['key' => 'akun', 'label' => 'Akun', 'route' => 'ganti-kata-sandi'],
    ];
@endphp

<nav class="hidden items-center gap-1 sm:flex" aria-label="Navigasi utama">
    @foreach ($tabs as $tab)
        @php $isActive = $active === $tab['key']; @endphp

        <a href="{{ route($tab['route']) }}" wire:navigate
           @if ($isActive) aria-current="page" @endif
           @class([
               'inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium transition-colors duration-150',
               'bg-accent-soft text-accent-text' => $isActive,
               'text-muted hover:bg-surface-muted hover:text-fg' => ! $isActive,
           ])>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
