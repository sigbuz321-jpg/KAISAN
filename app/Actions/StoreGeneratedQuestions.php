<?php

namespace App\Actions;

use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Models\AiGenerationJob;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class StoreGeneratedQuestions
{
    /**
     * Persists the questions that survived validation.
     *
     * Everything lands in one transaction so a job that dies halfway leaves no
     * half-written batch behind -- required by .claude/rules/testing.md.
     *
     * @param  list<array<string, mixed>>  $questions
     * @return array{saved: int, duplicates: int}
     */
    public function handle(AiGenerationJob $job, array $questions): array
    {
        if ($questions === []) {
            return ['saved' => 0, 'duplicates' => 0];
        }

        return DB::transaction(function () use ($job, $questions) {
            $existing = $this->existingHashes($job->subject_id, $questions);

            $saved = 0;
            $duplicates = 0;

            foreach ($questions as $question) {
                $hash = Question::hashStem((string) $question['stem']);

                // questions carries a unique (subject_id, stem_hash). Checking
                // first turns a would-be constraint violation into a number the
                // teacher can be shown.
                if (isset($existing[$hash])) {
                    $duplicates++;

                    continue;
                }

                $existing[$hash] = true;

                Question::create([
                    'subject_id' => $job->subject_id,
                    'topic_id' => $job->topic_id,
                    'stem' => $question['stem'],
                    'options' => $question['options'],
                    'answer_key' => $question['answer_key'],
                    'explanation' => $question['explanation'],
                    'difficulty' => $job->difficulty->toElo(),
                    'source' => QuestionSource::Ai,
                    // Rule 1 of .claude/rules/domain-kaisan.md: never anything
                    // but review, and no code path may offer an alternative.
                    'status' => QuestionStatus::Review,
                    'created_by' => $job->requested_by,
                ]);

                $saved++;
            }

            return ['saved' => $saved, 'duplicates' => $duplicates];
        });
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, true>
     */
    private function existingHashes(int $subjectId, array $questions): array
    {
        $hashes = array_map(
            fn (array $q) => Question::hashStem((string) $q['stem']),
            $questions
        );

        $found = Question::query()
            ->where('subject_id', $subjectId)
            ->whereIn('stem_hash', $hashes)
            ->pluck('stem_hash')
            ->all();

        return array_fill_keys(array_map(strval(...), $found), true);
    }
}
