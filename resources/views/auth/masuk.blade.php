@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <h1 class="text-2xl font-semibold sm:text-3xl">Masuk</h1>

    <x-ui.status />

    <form method="POST" action="{{ route('masuk.store') }}" class="mt-8 max-w-md space-y-5">
        @csrf

        <x-ui.input name="email" label="Email" type="email" required autofocus autocomplete="username" />
        <x-ui.input name="password" label="Kata sandi" type="password" required autocomplete="current-password" />

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
            Ingat saya di perangkat ini
        </label>

        <x-ui.button>Masuk</x-ui.button>
    </form>

    <p class="mt-6 text-sm">
        <a href="{{ route('lupa-kata-sandi') }}" class="text-slate-700 underline hover:text-slate-900">
            Lupa kata sandi?
        </a>
    </p>
@endsection
