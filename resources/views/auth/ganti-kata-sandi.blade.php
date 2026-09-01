@extends('layouts.app')

@section('title', 'Ganti kata sandi')

@section('content')
    <h1 class="text-2xl font-semibold sm:text-3xl">Ganti kata sandi</h1>

    <x-ui.status />

    <form method="POST" action="{{ route('ganti-kata-sandi.update') }}" class="mt-8 max-w-md space-y-5">
        @csrf
        @method('PUT')

        <x-ui.input name="current_password" label="Kata sandi lama" type="password" required />
        <x-ui.input name="password" label="Kata sandi baru" type="password" required hint="Minimal 8 karakter." />
        <x-ui.input name="password_confirmation" label="Ulangi kata sandi baru" type="password" required />

        <x-ui.button>Simpan</x-ui.button>
    </form>
@endsection
