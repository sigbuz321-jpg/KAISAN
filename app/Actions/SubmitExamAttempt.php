<?php

namespace App\Actions;

use App\Exceptions\ExamWorkflowException;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamWindow;
use App\Services\Scoring\ScoreResult;
use Illuminate\Support\Facades\Log;

class SubmitExamAttempt
{
    public function __construct(
        private readonly ExamWindow $window,
        private readonly GradeExamAttempt $grade,
    ) {}

    public function handle(ExamAttempt $attempt): ScoreResult
    {
        if ($attempt->isSubmitted()) {
            throw ExamWorkflowException::alreadySubmitted();
        }

        if ($this->window->hasExpired($attempt)) {
            throw ExamWorkflowException::timeIsUp();
        }

        $result = $this->grade->handle($attempt);

        // IDs only: .claude/rules/security.md forbids student names, emails or
        // answers in the log, and most of these students are minors.
        Log::info('attempt submitted', ['attempt_id' => $attempt->id]);

        return $result;
    }
}
