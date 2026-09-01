@extends('layouts.app')

@section('title', 'Kata sandi baru')

@section('content')
    <h1 class="text-2xl font-semibold sm:text-3xl">Buat kata sandi baru</h1>

    <form method="POST" action="{{ route('atur-ulang-kata-sandi.update') }}" class="mt-8 max-w-md space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-ui.input name="email" label="Email" type="email" :value="$email" required />
        <x-ui.input name="password" label="Kata sandi baru" type="password" required hint="Minimal 8 karakter." />
        <x-ui.input name="password_confirmation" label="Ulangi kata sandi baru" type="password" required />

        <x-ui.button>Simpan kata sandi</x-ui.button>
    </form>
@endsection
