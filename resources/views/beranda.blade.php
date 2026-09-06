@extends('layouts.app')

@section('title', __('app.name'))

@section('content')
    <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">{{ __('app.home.heading') }}</h1>

    <p class="mt-3 text-base leading-relaxed text-muted">{{ __('app.home.body') }}</p>

    <div class="mt-6">
        <x-ui.badge tone="success">{{ __('app.home.status') }}</x-ui.badge>
    </div>

    @guest
        <div class="mt-8 max-w-xs">
            <x-ui.button :href="route('masuk')">Masuk</x-ui.button>
        </div>
    @endguest
@endsection
