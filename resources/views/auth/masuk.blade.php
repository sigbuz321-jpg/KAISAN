@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">Masuk</h1>

    <x-ui.status />

    <form method="POST" action="{{ route('masuk.store') }}" class="mt-8 max-w-md space-y-5">
        @csrf

        <x-ui.input name="email" label="Email" type="email" required autofocus autocomplete="username" />
        <x-ui.input name="password" label="Kata sandi" type="password" required autocomplete="current-password" />

        <label class="flex min-h-11 items-center gap-2 text-sm text-fg">
            <input type="checkbox" name="remember" value="1"
                   class="h-4 w-4 rounded-sm border-border text-accent focus:ring-accent">
            Ingat saya di perangkat ini
        </label>

        <x-ui.button>Masuk</x-ui.button>
    </form>

    <p class="mt-6 text-sm">
        <a href="{{ route('lupa-kata-sandi') }}"
           class="font-medium text-accent-text underline-offset-2 hover:underline">Lupa kata sandi?</a>
    </p>
@endsection
