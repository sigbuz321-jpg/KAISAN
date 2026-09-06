@extends('layouts.app')

@section('title', 'Ujian - '.__('app.name'))
@section('tab', 'ujian')

@section('content')
    <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">Ujian</h1>

    @if (session('pesan'))
        <x-ui.banner tone="warning" class="mt-4">{{ session('pesan') }}</x-ui.banner>
    @endif

    @if ($exams->isEmpty())
        <x-ui.empty title="Belum ada ujian untuk kamu" class="mt-6">
            Kalau gurumu sudah menjadwalkan ujian, ujian itu akan muncul di halaman ini.
        </x-ui.empty>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($exams as $exam)
                @php
                    $attempt = $attempts[$exam->id] ?? null;
                    $sudahDikumpulkan = $attempt?->isSubmitted() ?? false;
                    $bisaDikerjakan = $exam->status->acceptsSubmissions() && ! $sudahDikumpulkan;
                @endphp

                <li class="rounded-lg border border-border bg-surface p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold leading-snug text-fg">{{ $exam->title }}</h2>
                            <p class="mt-0.5 text-xs text-muted">{{ $exam->subject->name }}</p>
                        </div>

                        <x-ui.badge :tone="$exam->status->acceptsSubmissions() ? 'success' : 'neutral'" class="shrink-0">
                            {{ $exam->status->label() }}
                        </x-ui.badge>
                    </div>

                    <dl class="mt-4 grid gap-x-6 gap-y-1.5 text-sm text-fg sm:grid-cols-2">
                        <div class="flex gap-2">
                            <dt class="text-muted">Mulai</dt>
                            <dd>{{ $exam->starts_at->translatedFormat('d M Y, H:i') }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-muted">Selesai</dt>
                            <dd>{{ $exam->ends_at->translatedFormat('d M Y, H:i') }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-muted">Durasi</dt>
                            <dd>{{ $exam->duration_minutes }} menit</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-muted">Jumlah soal</dt>
                            <dd>{{ $exam->question_count }} soal</dd>
                        </div>
                    </dl>

                    <div class="mt-5">
                        @if ($sudahDikumpulkan)
                            @if ($attempt->score !== null)
                                <p class="text-base text-fg">
                                    Sudah dikumpulkan. Nilai kamu:
                                    <strong class="font-semibold">{{ rtrim(rtrim($attempt->score, '0'), '.') }}</strong>.
                                </p>
                            @else
                                <p class="text-base text-fg">
                                    Sudah dikumpulkan. Nilai akan muncul setelah ujian ditutup.
                                </p>
                            @endif
                        @elseif ($bisaDikerjakan)
                            <x-ui.button :href="route('ujian.kerjakan', $exam)" :block="false">
                                {{ $attempt ? 'Lanjutkan ujian' : 'Mulai ujian' }}
                            </x-ui.button>

                            @if ($attempt)
                                <p class="mt-2 text-sm text-muted">
                                    Kamu sudah mulai mengerjakan. Waktumu tetap berjalan sejak pertama kali mulai.
                                </p>
                            @endif
                        @elseif ($exam->status === App\Enums\ExamStatus::Scheduled)
                            <p class="text-base text-muted">Ujian belum dimulai.</p>
                        @else
                            <p class="text-base text-muted">
                                Ujian sudah ditutup{{ $attempt ? ' dan pengerjaanmu sudah dinilai' : '' }}.
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
