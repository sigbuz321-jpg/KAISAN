@extends('layouts.app')

@section('title', 'Latihan - '.__('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Latihan</h1>

    <p class="mt-2 text-base text-slate-700">
        Soal menyesuaikan kemampuanmu: makin sering benar, makin menantang.
        Latihan tidak mempengaruhi peringkat, jadi santai saja.
    </p>

    @if ($subjects->isEmpty())
        <p class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-base text-slate-700">
            Belum ada mata pelajaran yang bisa dilatih.
        </p>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($subjects as $subject)
                @php
                    $ability = $abilities[$subject->id] ?? null;
                    $level = $ability?->level();
                    $adaSoal = $subject->published_questions_count > 0;
                @endphp

                <li class="rounded-lg border border-slate-200 bg-white p-4 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $subject->name }}</h2>

                        @if ($level)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-800">
                                {{ $level->label() }}
                            </span>
                        @else
                            <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-500">
                                Belum dimulai
                            </span>
                        @endif
                    </div>

                    @if ($ability)
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-slate-900" style="width: {{ $ability->progress() }}%"></div>
                        </div>

                        <p class="mt-2 text-sm text-slate-600">
                            {{ $ability->answers_count }} soal sudah kamu kerjakan di mata pelajaran ini.
                        </p>
                    @endif

                    <div class="mt-5">
                        @if ($adaSoal)
                            <a href="{{ route('latihan.mulai', $subject) }}" wire:navigate
                               class="inline-block min-h-11 rounded bg-slate-900 px-5 py-2.5 text-base font-medium text-white hover:bg-slate-800">
                                {{ $ability ? 'Lanjut berlatih' : 'Mulai berlatih' }}
                            </a>
                        @else
                            <p class="text-base text-slate-700">
                                Belum ada soal di mata pelajaran ini.
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
