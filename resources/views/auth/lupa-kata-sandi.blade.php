@extends('layouts.app')

@section('title', 'Lupa kata sandi')

@section('content')
    <h1 class="text-2xl font-semibold sm:text-3xl">Lupa kata sandi</h1>

    <p class="mt-3 text-base text-slate-700">
        Masukkan email Anda. Kami kirimkan tautan untuk membuat kata sandi baru.
    </p>

    <x-ui.status />

    <form method="POST" action="{{ route('lupa-kata-sandi.email') }}" class="mt-8 max-w-md space-y-5">
        @csrf
        <x-ui.input name="email" label="Email" type="email" required autofocus />
        <x-ui.button>Kirim tautan</x-ui.button>
    </form>

    <p class="mt-6 text-sm">
        <a href="{{ route('masuk') }}" class="text-slate-700 underline hover:text-slate-900">Kembali ke halaman masuk</a>
    </p>
@endsection
