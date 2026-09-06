@extends('layouts.app')

@section('title', 'Pemasangan awal')

@section('content')
    <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">Buat akun admin pertama</h1>

    <p class="mt-3 text-base leading-relaxed text-muted">
        Halaman ini hanya muncul sekali. Setelah akun admin dibuat, halaman ini
        tidak bisa dibuka lagi.
    </p>

    @if ($errors->any())
        <x-ui.banner tone="danger" title="Periksa kembali isian berikut:" class="mt-6">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.banner>
    @endif

    <form method="POST" action="{{ route('setup.store') }}" class="mt-8 max-w-md space-y-5">
        @csrf

        <x-ui.input name="name" label="Nama lengkap" required autofocus />
        <x-ui.input name="email" label="Email" type="email" required />
        <x-ui.input name="password" label="Kata sandi" type="password" required hint="Minimal 8 karakter." />
        <x-ui.input name="password_confirmation" label="Ulangi kata sandi" type="password" required />

        <x-ui.button>Buat akun admin</x-ui.button>
    </form>
@endsection
