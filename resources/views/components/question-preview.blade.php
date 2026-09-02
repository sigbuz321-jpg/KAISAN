@props(['question', 'showAnswer' => false, 'number' => 1])

{{--
    A teacher's preview of one question.

    The markup comes from <x-question-body>, the same component the live exam
    screen renders, so what a teacher checks here is what a student is given.

    answer_key and explanation appear only when showAnswer is explicitly set,
    and showAnswer is never true on a student-facing page
    (.claude/rules/security.md).
--}}
<x-question-body
    :number="$number"
    :stem="$question->stem"
    :options="$question->orderedOptions()"
    :name="'pratinjau-'.$question->id"
    :highlight="$showAnswer ? $question->answer_key : null"
>
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
</x-question-body>
