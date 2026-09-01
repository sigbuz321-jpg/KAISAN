@props(['question', 'showAnswer' => false, 'number' => 1])

{{--
    The single rendering of a question as a student sees it. The exam screen in
    M4 uses this same component, so what a teacher previews here cannot drift
    away from what students actually get.

    answer_key and explanation are hidden unless showAnswer is explicitly set,
    and showAnswer is never true on a student-facing page
    (.claude/rules/security.md).
--}}
<article class="rounded-lg border border-slate-200 bg-white p-4 sm:p-6">
    <p class="text-sm font-medium text-slate-500">Soal nomor {{ $number }}</p>

    <p class="mt-2 whitespace-pre-line text-base leading-relaxed text-slate-900">{{ $question->stem }}</p>

    <ul class="mt-5 space-y-2">
        @foreach ($question->orderedOptions() as $key => $text)
            <li>
                <label class="flex min-h-11 cursor-pointer items-start gap-3 rounded border border-slate-300
                              bg-white p-3 hover:border-slate-400
                              @if ($showAnswer && $key === $question->answer_key) border-emerald-500 bg-emerald-50 @endif">
                    <input type="radio" name="pratinjau-{{ $question->id }}" value="{{ $key }}" disabled
                           class="mt-1 h-4 w-4 border-slate-400">
                    <span class="text-base text-slate-900">
                        <span class="font-semibold">{{ $key }}.</span>
                        {{ $text }}
                    </span>
                </label>
            </li>
        @endforeach
    </ul>

    @if ($showAnswer)
        <div class="mt-5 rounded border border-emerald-300 bg-emerald-50 p-3">
            <p class="text-sm font-semibold text-emerald-900">
                Kunci jawaban: {{ $question->answer_key }}
            </p>

            @if ($question->explanation)
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-emerald-900">
                    {{ $question->explanation }}
                </p>
            @endif

            <p class="mt-2 text-xs text-emerald-800">
                Bagian ini hanya terlihat oleh guru. Murid tidak pernah menerimanya.
            </p>
        </div>
    @endif
</article>
