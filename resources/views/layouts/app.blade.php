<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FFFFFF">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-bg font-sans text-fg antialiased">
    <a href="#konten"
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50
              focus:rounded-md focus:bg-fg focus:px-4 focus:py-2 focus:text-bg">
        {{ __('app.skip_to_content') }}
    </a>

    @php
        $murid = auth()->check() && auth()->user()->isMurid();
        $tab = trim($__env->yieldContent('tab')) ?: null;
    @endphp

    <header class="sticky top-0 z-10 border-b border-border bg-surface">
        <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3">
            <a href="{{ route('beranda') }}"
               class="text-base font-semibold tracking-[-0.01em] text-fg">{{ __('app.name') }}</a>

            @auth
                @if ($murid)
                    <x-ui.nav-links :active="$tab" class="ms-4" />
                @endif

                <div class="ms-auto flex items-center gap-1">
                    @if (auth()->user()->role->canAccessPanel())
                        <a href="/admin"
                           class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-muted
                                  transition-colors duration-150 hover:bg-surface-muted hover:text-fg">Panel</a>
                    @endif

                    @unless ($murid)
                        <a href="{{ route('ganti-kata-sandi') }}"
                           class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-muted
                                  transition-colors duration-150 hover:bg-surface-muted hover:text-fg">Akun</a>
                    @endunless

                    <form method="POST" action="{{ route('keluar') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-muted
                                       transition-colors duration-150 hover:bg-surface-muted hover:text-fg">
                            Keluar
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('masuk') }}"
                   class="ms-auto inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-muted
                          transition-colors duration-150 hover:bg-surface-muted hover:text-fg">Masuk</a>
            @endauth
        </div>
    </header>

    <main id="konten" class="mx-auto w-full max-w-3xl flex-1 px-4 py-8">
        @yield('content')
    </main>

    @if ($murid)
        <x-ui.tab-bar :active="$tab" />
    @endif
</body>
</html>
