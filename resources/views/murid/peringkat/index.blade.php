@extends('layouts.app')

@section('title', 'Peringkat - '.__('app.name'))
@section('tab', 'peringkat')

@section('content')
    <h1 class="text-[1.75rem] font-semibold leading-tight tracking-[-0.015em] text-fg">Peringkat</h1>

    @if ($season)
        <div class="mt-4 rounded-lg border border-border bg-surface p-4">
            <p class="text-xs font-medium uppercase tracking-[0.06em] text-muted">Musim berjalan</p>
            <p class="mt-1 text-lg font-semibold text-fg">{{ $season->name }}</p>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                Peringkat dihitung dari nilai ujian. Latihan tidak menambah poin.
            </p>
        </div>
    @endif

    <x-ui.tab-pill label="Pilih papan peringkat" class="mt-6 max-w-full flex-wrap">
        <x-ui.tab-pill-item :href="route('peringkat.index')" :active="$subjectId === null">
            Gabungan
        </x-ui.tab-pill-item>

        @foreach ($subjects as $subject)
            <x-ui.tab-pill-item :href="route('peringkat.index', ['mapel' => $subject->id])"
                                :active="$subjectId === $subject->id">
                {{ $subject->name }}
            </x-ui.tab-pill-item>
        @endforeach
    </x-ui.tab-pill>

    @if (! $season)
        <x-ui.empty title="Belum ada musim yang berjalan" class="mt-6">
            Peringkat akan muncul setelah admin memulai musim baru. Sementara itu kamu tetap
            bisa berlatih dan mengikuti ujian seperti biasa.
        </x-ui.empty>
    @elseif ($top->isEmpty())
        <x-ui.empty title="Belum ada nilai ujian di musim ini" class="mt-6">
            Peringkat muncul setelah ujian pertama dinilai.

            <x-slot:action>
                <x-ui.button :href="route('ujian.index')" variant="secondary" :block="false">
                    Lihat ujian yang tersedia
                </x-ui.button>
            </x-slot:action>
        </x-ui.empty>
    @else
        @php
            $podiumEntries = $top->take(3)->map(fn ($e) => [
                'rank' => (int) $e->rank,
                'name' => $e->student->name,
                'points' => number_format((float) $e->points, 0, ',', '.'),
            ])->values()->all();
        @endphp

        {{--
            The podium needs all three places filled. Below that the list says
            everything on its own, and a podium holding one student just repeats
            the same name twice in a row.
        --}}
        @if (count($podiumEntries) >= 3)
            <div data-react-island="leaderboard-podium" data-react-props="{{ json_encode(['entries' => $podiumEntries]) }}"></div>
        @endif

        <ol class="mt-6 rounded-lg border border-border bg-surface px-1 py-1">
            @foreach ($top as $entry)
                <x-ui.leaderboard-row
                    :rank="$entry->rank"
                    :name="$entry->student->name"
                    :points="number_format((float) $entry->points, 0, ',', '.')"
                    :is-me="$mine && $entry->id === $mine->id"
                />
            @endforeach
        </ol>

        @if ($mine && ! $top->contains('id', $mine->id))
            <div class="mt-4">
                <p class="mb-2 text-xs font-medium uppercase tracking-[0.06em] text-muted">Posisimu</p>

                <ol class="rounded-lg border border-border bg-surface px-1 py-1">
                    <x-ui.leaderboard-row
                        :rank="$mine->rank"
                        :name="$mine->student->name"
                        :points="number_format((float) $mine->points, 0, ',', '.')"
                        is-me
                    />
                </ol>
            </div>
        @elseif (! $mine)
            <x-ui.empty class="mt-4">
                Kamu belum punya poin di papan ini.

                <x-slot:action>
                    <x-ui.button :href="route('ujian.index')" variant="secondary" :block="false">
                        Lihat ujian yang tersedia
                    </x-ui.button>
                </x-slot:action>
            </x-ui.empty>
        @endif

        <p class="mt-6 text-sm text-muted">
            Peringkat diperbarui setiap beberapa menit, jadi nilai ujian yang baru saja
            keluar mungkin belum terhitung.
        </p>
    @endif
@endsection
