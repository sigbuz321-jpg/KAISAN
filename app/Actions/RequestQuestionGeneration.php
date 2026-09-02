<?php

namespace App\Actions;

use App\Enums\DifficultyBand;
use App\Exceptions\AiQuotaException;
use App\Jobs\GenerateQuestionsJob;
use App\Models\AiGenerationJob;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;

class RequestQuestionGeneration
{
    /**
     * Records a generation request and queues the work.
     *
     * The HTTP request ends here. Calling the router from inside it would tie
     * up a PHP-FPM worker for the length of a model call, which
     * .claude/rules/performance.md forbids.
     */
    public function handle(
        User $teacher,
        Subject $subject,
        ?Topic $topic,
        DifficultyBand $difficulty,
        int $count,
        ?int $grade = null,
    ): AiGenerationJob {
        $this->guardQuota($teacher, $count);

        $job = new AiGenerationJob([
            'requested_by' => $teacher->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic?->id,
            'difficulty' => $difficulty,
            'count' => $count,
        ]);

        // The grade steers the wording of the prompt but is not part of the
        // documented schema, so it rides along in meta.
        $job->meta = $grade === null ? null : ['grade' => $grade];
        $job->save();

        GenerateQuestionsJob::dispatch($job->id);

        return $job;
    }

    private function guardQuota(User $teacher, int $count): void
    {
        $max = (int) config('kaisan.ai.max_questions_per_job', AiGenerationJob::MAX_QUESTIONS_PER_JOB);

        if ($count < 1 || $count > $max) {
            throw AiQuotaException::tooManyQuestions($max);
        }

        $perHour = (int) config('kaisan.ai.jobs_per_hour', 20);

        // Counted from the table rather than a cache bucket: this is a spending
        // limit, and it must survive a Redis flush.
        $recent = AiGenerationJob::query()
            ->where('requested_by', $teacher->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= $perHour) {
            throw AiQuotaException::tooManyJobs($perHour);
        }
    }
}
