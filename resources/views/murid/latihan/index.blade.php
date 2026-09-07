@extends('layouts.app')

@section('title', 'Latihan - '.__('app.name'))
@section('tab', 'latihan')

@section('content')
    <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">Latihan</h1>

    <p class="mt-2 text-base leading-relaxed text-muted">
        Soal menyesuaikan kemampuanmu: makin sering benar, makin menantang.
    </p>

    {{-- Point 1 of 3 where the contract line appears: index, after a wrong
         answer, and on the session summary. --}}
    <x-ui.banner tone="info" class="mt-4">
        Latihan tidak menambah poin peringkat. Yang dihitung untuk peringkat hanya ujian.
    </x-ui.banner>

    @if ($subjects->isEmpty())
        <x-ui.empty title="Belum ada mata pelajaran" class="mt-6">
            Mata pelajaran akan muncul di sini setelah gurumu menyiapkannya.
        </x-ui.empty>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($subjects as $subject)
                <x-ui.subject-card
                    :subject="$subject"
                    :ability="$abilities[$subject->id] ?? null"
                    :available="$subject->published_questions_count > 0"
                    :href="$subject->published_questions_count > 0 ? route('latihan.mulai', $subject) : null"
                />
            @endforeach
        </ul>
    @endif
@endsection
