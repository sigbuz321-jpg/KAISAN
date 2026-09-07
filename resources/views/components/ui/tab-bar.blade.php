@props(['active' => null])

{{--
    Tahap 2 · Navigasi utama murid.

    A bottom tab bar on a phone, where thumbs are, and a plain row inside the
    header on anything wider. Four destinations, no menu to open.
--}}
@php
    $tabs = [
        ['key' => 'latihan', 'label' => 'Latihan', 'icon' => 'icon.book', 'route' => 'latihan.index'],
        ['key' => 'ujian', 'label' => 'Ujian', 'icon' => 'icon.clipboard', 'route' => 'ujian.index'],
        ['key' => 'peringkat', 'label' => 'Peringkat', 'icon' => 'icon.podium', 'route' => 'peringkat.index'],
        ['key' => 'akun', 'label' => 'Akun', 'icon' => 'icon.user', 'route' => 'ganti-kata-sandi'],
    ];
@endphp

<nav {{ $attributes->merge([
    'class' => 'sticky bottom-0 z-10 grid h-16 grid-cols-4 border-t border-border bg-surface sm:hidden',
]) }}
     aria-label="Navigasi utama">
    @foreach ($tabs as $tab)
        @php $isActive = $active === $tab['key']; @endphp

        <a href="{{ route($tab['route']) }}" wire:navigate
           @if ($isActive) aria-current="page" @endif
           @class([
               'flex flex-col items-center justify-center gap-1 text-[11px] tracking-[0.04em] transition-colors duration-150',
               'text-accent' => $isActive,
               'text-muted hover:text-fg' => ! $isActive,
           ])>
            <x-dynamic-component :component="$tab['icon']" class="h-6 w-6 {{ $isActive ? '' : 'opacity-70' }}" />
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
