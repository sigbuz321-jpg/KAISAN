<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <a href="#konten"
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50
              focus:rounded focus:bg-slate-900 focus:px-4 focus:py-2 focus:text-white">
        {{ __('app.skip_to_content') }}
    </a>

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-x-4 gap-y-2 px-4 py-4">
            <a href="{{ route('beranda') }}" class="text-lg font-semibold">{{ __('app.name') }}</a>

            <nav class="ms-auto flex items-center gap-4 text-sm">
                @auth
                    @if (auth()->user()->role->canAccessPanel())
                        <a href="/admin" class="text-slate-700 underline hover:text-slate-900">Panel</a>
                    @endif

                    @if (auth()->user()->isMurid())
                        <a href="{{ route('latihan.index') }}" wire:navigate
                           class="text-slate-700 underline hover:text-slate-900">Latihan</a>

                        <a href="{{ route('ujian.index') }}" wire:navigate
                           class="text-slate-700 underline hover:text-slate-900">Ujian</a>

                        <a href="{{ route('peringkat.index') }}" wire:navigate
                           class="text-slate-700 underline hover:text-slate-900">Peringkat</a>
                    @endif

                    <a href="{{ route('ganti-kata-sandi') }}" class="text-slate-700 underline hover:text-slate-900">
                        Ganti kata sandi
                    </a>

                    <form method="POST" action="{{ route('keluar') }}">
                        @csrf
                        <button type="submit" class="min-h-11 text-slate-700 underline hover:text-slate-900">
                            Keluar
                        </button>
                    </form>
                @else
                    <a href="{{ route('masuk') }}" class="text-slate-700 underline hover:text-slate-900">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="konten" class="mx-auto max-w-3xl px-4 py-10">
        @yield('content')
    </main>
</body>
</html>
