@extends('layouts.app')

@section('title', 'Kata sandi baru')

@section('content')
    {{-- Auth screens keep heading, form and links on one narrow measure, centred.
         The form alone used to be max-w-md inside the full-width column, so it
         sat off to one side of its own heading. --}}
    <div class="mx-auto max-w-md">
        <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">Buat kata sandi baru</h1>

        <form method="POST" action="{{ route('atur-ulang-kata-sandi.update') }}" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <x-ui.input name="email" label="Email" type="email" :value="$email" required />
            <x-ui.input name="password" label="Kata sandi baru" type="password" required hint="Minimal 8 karakter." />
            <x-ui.input name="password_confirmation" label="Ulangi kata sandi baru" type="password" required />

            <x-ui.button>Simpan kata sandi</x-ui.button>
        </form>
    </div>
@endsection
