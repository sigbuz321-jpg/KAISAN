@extends('layouts.app')

@section('title', 'Peringkat - '.__('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Peringkat</h1>

    @if ($season)
        <p class="mt-2 text-base text-slate-700">
            Musim berjalan: <strong>{{ $season->name }}</strong>.
            Peringkat dihitung dari nilai ujian. Latihan tidak menambah poin.
        </p>
    @endif

    <nav class="mt-6 flex flex-wrap gap-2" aria-label="Pilih papan peringkat">
        <a href="{{ route('peringkat.index') }}" wire:navigate
           @class([
               'min-h-11 rounded-full px-4 py-2.5 text-sm font-medium',
               'bg-slate-900 text-white' => $subjectId === null,
               'border border-slate-300 bg-white text-slate-800' => $subjectId !== null,
           ])>
            Gabungan
        </a>

        @foreach ($subjects as $subject)
            <a href="{{ route('peringkat.index', ['mapel' => $subject->id]) }}" wire:navigate
               @class([
                   'min-h-11 rounded-full px-4 py-2.5 text-sm font-medium',
                   'bg-slate-900 text-white' => $subjectId === $subject->id,
                   'border border-slate-300 bg-white text-slate-800' => $subjectId !== $subject->id,
               ])>
                {{ $subject->name }}
            </a>
        @endforeach
    </nav>

    @if (! $season)
        <p class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-base text-slate-700">
            Belum ada musim yang berjalan. Peringkat akan muncul setelah admin memulai musim baru.
        </p>
    @elseif ($top->isEmpty())
        <p class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-base text-slate-700">
            Belum ada nilai ujian di musim ini. Peringkat muncul setelah ujian pertama dinilai.
        </p>
    @else
        <ol class="mt-6 space-y-2">
            @foreach ($top as $entry)
                <li @class([
                    'flex items-center gap-4 rounded-lg border bg-white p-3 sm:p-4',
                    'border-slate-900 ring-1 ring-slate-900' => $mine && $entry->id === $mine->id,
                    'border-slate-200' => ! ($mine && $entry->id === $mine->id),
                ])>
                    <span class="w-10 shrink-0 text-center text-lg font-semibold text-slate-900">
                        {{ $entry->rank }}
                    </span>

                    <span class="flex-1 text-base text-slate-900">
                        {{ $entry->student->name }}
                        @if ($mine && $entry->id === $mine->id)
                            <span class="ms-1 text-sm text-slate-500">(kamu)</span>
                        @endif
                    </span>

                    <span class="shrink-0 text-base font-medium text-slate-900">
                        {{ number_format((float) $entry->points, 0, ',', '.') }}
                    </span>
                </li>
            @endforeach
        </ol>

        @if ($mine && ! $top->contains('id', $mine->id))
            <div class="mt-4">
                <p class="mb-2 text-sm text-slate-600">Posisimu</p>

                <div class="flex items-center gap-4 rounded-lg border border-slate-900 bg-white p-3 ring-1 ring-slate-900 sm:p-4">
                    <span class="w-10 shrink-0 text-center text-lg font-semibold text-slate-900">{{ $mine->rank }}</span>
                    <span class="flex-1 text-base text-slate-900">{{ $mine->student->name }} <span class="text-sm text-slate-500">(kamu)</span></span>
                    <span class="shrink-0 text-base font-medium text-slate-900">
                        {{ number_format((float) $mine->points, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        @elseif (! $mine)
            <p class="mt-4 rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700">
                Kamu belum punya poin di papan ini. Ikut ujian untuk mulai masuk peringkat.
            </p>
        @endif

        <p class="mt-6 text-sm text-slate-500">
            Peringkat diperbarui setiap beberapa menit, jadi nilai ujian yang baru saja
            keluar mungkin belum terhitung.
        </p>
    @endif
@endsection
