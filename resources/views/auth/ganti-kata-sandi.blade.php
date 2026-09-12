@extends('layouts.app')

@section('title', 'Ganti kata sandi')
@section('tab', 'akun')

@section('content')
    {{-- Auth screens keep heading, form and links on one narrow measure, centred.
         The form alone used to be max-w-md inside the full-width column, so it
         sat off to one side of its own heading. --}}
    <div class="mx-auto max-w-md">
        <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">Ganti kata sandi</h1>

        <x-ui.status />

        <form method="POST" action="{{ route('ganti-kata-sandi.update') }}" class="mt-8 space-y-5">
            @csrf
            @method('PUT')

            <x-ui.input name="current_password" label="Kata sandi lama" type="password" required />
            <x-ui.input name="password" label="Kata sandi baru" type="password" required hint="Minimal 8 karakter." />
            <x-ui.input name="password_confirmation" label="Ulangi kata sandi baru" type="password" required />

            <x-ui.button>Simpan</x-ui.button>
        </form>

        <form method="POST" action="{{ route('keluar') }}" class="mt-8 border-t border-border pt-6">
            @csrf
            <x-ui.button variant="secondary">Keluar dari akun</x-ui.button>
        </form>
    </div>
@endsection
