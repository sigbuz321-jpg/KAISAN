@extends('layouts.app')

@section('title', 'Pemasangan awal')

@section('content')
    <h1 class="text-2xl font-semibold sm:text-3xl">Buat akun admin pertama</h1>

    <p class="mt-3 text-base leading-relaxed text-slate-700">
        Halaman ini hanya muncul sekali. Setelah akun admin dibuat, halaman ini
        tidak bisa dibuka lagi.
    </p>

    @if ($errors->any())
        <div class="mt-6 rounded border border-red-300 bg-red-50 p-4 text-sm text-red-900">
            <p class="font-medium">Periksa kembali isian berikut:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('setup.store') }}" class="mt-8 max-w-md space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-900">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                   class="mt-1 block w-full rounded border border-slate-300 px-3 py-2 text-base
                          focus:border-slate-900 focus:ring-slate-900">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-900">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   class="mt-1 block w-full rounded border border-slate-300 px-3 py-2 text-base
                          focus:border-slate-900 focus:ring-slate-900">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-900">Kata sandi</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 block w-full rounded border border-slate-300 px-3 py-2 text-base
                          focus:border-slate-900 focus:ring-slate-900">
            <p class="mt-1 text-sm text-slate-600">Minimal 8 karakter.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-900">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="mt-1 block w-full rounded border border-slate-300 px-3 py-2 text-base
                          focus:border-slate-900 focus:ring-slate-900">
        </div>

        <button type="submit"
                class="min-h-11 w-full rounded bg-slate-900 px-4 py-2.5 text-base font-medium text-white
                       hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
            Buat akun admin
        </button>
    </form>
@endsection
