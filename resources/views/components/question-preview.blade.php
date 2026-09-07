@props(['question', 'showAnswer' => false, 'number' => 1])

{{--
    A teacher's preview of one question.

    The markup comes from <x-ui.question-card>, the same component the live exam
    screen renders, so what a teacher checks here is what a student is given.

    answer_key and explanation appear only when showAnswer is explicitly set,
    and showAnswer is never true on a student-facing page
    (.claude/rules/security.md).
--}}
<x-ui.question-card
    :number="$number"
    :stem="$question->stem"
    :options="$question->orderedOptions()"
    :name="'pratinjau-'.$question->id"
    :highlight="$showAnswer ? $question->answer_key : null"
>
    @if ($showAnswer)
        <div class="mt-5 rounded-md bg-success-soft p-3">
            <p class="text-sm font-semibold text-success-text">
                Kunci jawaban: {{ $question->answer_key }}
            </p>

            @if ($question->explanation)
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-success-text">
                    {{ $question->explanation }}
                </p>
            @endif
        </div>
    @endif
</x-ui.question-card>
