@extends('layouts.app')

@section('title', 'Ujian - '.__('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Ujian</h1>

    @if (session('pesan'))
        <p class="mt-4 rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
            {{ session('pesan') }}
        </p>
    @endif

    @if ($exams->isEmpty())
        <p class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-base text-slate-700">
            Belum ada ujian untuk kamu saat ini. Kalau gurumu sudah menjadwalkan ujian,
            ujian itu akan muncul di halaman ini.
        </p>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($exams as $exam)
                @php
                    $attempt = $attempts[$exam->id] ?? null;
                    $sudahDikumpulkan = $attempt?->isSubmitted() ?? false;
                    $bisaDikerjakan = $exam->status->acceptsSubmissions() && ! $sudahDikumpulkan;
                @endphp

                <li class="rounded-lg border border-slate-200 bg-white p-4 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ $exam->title }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $exam->subject->name }}</p>
                        </div>

                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-medium',
                            'bg-emerald-100 text-emerald-900' => $exam->status->acceptsSubmissions(),
                            'bg-slate-100 text-slate-700' => ! $exam->status->acceptsSubmissions(),
                        ])>
                            {{ $exam->status->label() }}
                        </span>
                    </div>

                    <dl class="mt-4 grid gap-x-6 gap-y-1 text-sm text-slate-700 sm:grid-cols-2">
                        <div class="flex gap-2">
                            <dt class="text-slate-500">Mulai</dt>
                            <dd>{{ $exam->starts_at->translatedFormat('d M Y, H:i') }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-slate-500">Selesai</dt>
                            <dd>{{ $exam->ends_at->translatedFormat('d M Y, H:i') }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-slate-500">Durasi</dt>
                            <dd>{{ $exam->duration_minutes }} menit</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-slate-500">Jumlah soal</dt>
                            <dd>{{ $exam->question_count }} soal</dd>
                        </div>
                    </dl>

                    <div class="mt-5">
                        @if ($sudahDikumpulkan)
                            <p class="text-base text-slate-900">
                                Sudah dikumpulkan.
                                @if ($attempt->score !== null)
                                    Nilai kamu:
                                    <strong>{{ rtrim(rtrim($attempt->score, '0'), '.') }}</strong>.
                                @else
                                    Nilai akan muncul setelah ujian ditutup.
                                @endif
                            </p>
                        @elseif ($bisaDikerjakan)
                            <a href="{{ route('ujian.kerjakan', $exam) }}" wire:navigate
                               class="inline-block min-h-11 rounded bg-slate-900 px-5 py-2.5 text-base font-medium text-white hover:bg-slate-800">
                                {{ $attempt ? 'Lanjutkan ujian' : 'Mulai ujian' }}
                            </a>

                            @if ($attempt)
                                <p class="mt-2 text-sm text-slate-600">
                                    Kamu sudah mulai mengerjakan. Waktumu tetap berjalan sejak pertama kali mulai.
                                </p>
                            @endif
                        @elseif ($exam->status === App\Enums\ExamStatus::Scheduled)
                            <p class="text-base text-slate-700">Ujian belum dimulai.</p>
                        @else
                            <p class="text-base text-slate-700">
                                Ujian sudah ditutup{{ $attempt ? ' dan pengerjaanmu sudah dinilai' : '' }}.
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
