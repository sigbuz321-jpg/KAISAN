@extends('layouts.app')

@section('title', __('app.name'))

@section('content')
    <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">
        {{ __('app.home.heading') }}
    </h1>

    <p class="mt-3 text-base leading-relaxed text-muted">{{ __('app.home.body') }}</p>

    @auth
        {{-- Already signed in: one way on, to wherever this role belongs. --}}
        <div class="mt-8 max-w-xs">
            @if (auth()->user()->isMurid())
                <x-ui.button :href="route('latihan.index')">Lanjutkan belajar</x-ui.button>
            @else
                <x-ui.button :href="auth()->user()->isAdmin() ? '/admin' : '/guru'">Buka panel</x-ui.button>
            @endif
        </div>
    @else
        {{--
            Three doors, because the three roles genuinely sign in at three
            addresses: students on their own pages, staff through their panel.
            Naming them here saves a teacher from trying the student form and
            concluding the site is broken.
        --}}
        <h2 class="mt-8 text-xs font-medium uppercase tracking-[0.06em] text-muted">
            {{ __('app.home.pilih') }}
        </h2>

        @php
            $pintuList = [
                [
                    'label' => 'Murid',
                    'meta' => 'Latihan adaptif, ujian terjadwal, dan peringkat.',
                    'href' => route('masuk'),
                    'icon' => 'book',
                    'tone' => 'accent',
                ],
                [
                    'label' => 'Guru',
                    'meta' => 'Bank soal, jadwal ujian, dan nilai kelas.',
                    'href' => '/guru/login',
                    'icon' => 'clipboard',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Admin',
                    'meta' => 'Akun, mata pelajaran, musim, dan biaya AI.',
                    'href' => '/admin/login',
                    'icon' => 'user',
                    'tone' => 'neutral',
                ],
            ];
        @endphp

        {{-- React Island with Motion.dev stagger entry and spring gestures --}}
        <div data-react-island="home-pillars" data-react-props="@json(['items' => $pintuList])">
            <ul class="mt-3 space-y-3">
                @foreach ($pintuList as $pintu)
                    <li>
                        <a href="{{ $pintu['href'] }}"
                           class="flex items-center gap-3 rounded-lg border border-border bg-surface p-4
                                  transition-shadow duration-150 hover:shadow-elevated
                                  focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2
                                  focus-visible:outline-accent">
                            <span @class([
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-md',
                                'bg-accent-soft text-accent-text' => $pintu['tone'] === 'accent',
                                'bg-info-soft text-info-text' => $pintu['tone'] === 'info',
                                'bg-surface-muted text-muted' => $pintu['tone'] === 'neutral',
                            ])>
                                <x-dynamic-component :component="'icon.'.$pintu['icon']" class="h-5 w-5" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-base font-semibold text-fg">{{ $pintu['label'] }}</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-muted">{{ $pintu['meta'] }}</span>
                            </span>

                            <x-icon.chevron-right class="h-5 w-5 shrink-0 text-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endauth
@endsection
