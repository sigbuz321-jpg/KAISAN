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
        <div class="mx-auto flex max-w-3xl items-center px-4 py-4">
            <span class="text-lg font-semibold">{{ __('app.name') }}</span>
        </div>
    </header>

    <main id="konten" class="mx-auto max-w-3xl px-4 py-10">
        @yield('content')
    </main>
</body>
</html>
