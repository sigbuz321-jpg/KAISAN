<?php

namespace App\Actions;

use App\Exceptions\ExamWorkflowException;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamPaper;
use App\Services\Exams\ExamWindow;

class SaveAttemptAnswer
{
    public function __construct(
        private readonly ExamWindow $window,
        private readonly ExamPaper $paper,
    ) {}

    /**
     * Records one choice, mid-exam.
     *
     * Note what is NOT written here: is_correct stays null until grading.
     * Marking it now would put the answer key inside the student's own row
     * while the exam is still running, and any leak of that row -- a debug
     * page, an export, a future endpoint -- would hand over the answers.
     */
    public function handle(ExamAttempt $attempt, int $questionId, ?string $option): AttemptAnswer
    {
        if ($attempt->isSubmitted()) {
            throw ExamWorkflowException::alreadySubmitted();
        }

        if ($this->window->hasExpired($attempt)) {
            throw ExamWorkflowException::timeIsUp();
        }

        $question = $attempt->exam->questions()->whereKey($questionId)->first();

        if ($question === null) {
            throw ExamWorkflowException::questionNotInExam();
        }

        // The student clicked a letter on a possibly shuffled paper. Store the
        // letter the question was written with, so grading never has to know
        // that shuffling happened.
        $stored = $this->paper->toStoredOption($attempt, $question, $option);

        // Keyed on the unique (exam_attempt_id, question_id), so a student
        // changing their mind updates one row and a retried request after a
        // dropped connection is harmless.
        return AttemptAnswer::updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'question_id' => $questionId],
            ['selected_option' => $stored, 'answered_at' => now()],
        );
    }
}
