<?php

namespace App\Actions;

use App\Enums\AttemptStatus;
use App\Exceptions\ExamWorkflowException;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class StartExamAttempt
{
    /**
     * Opens a student's attempt, or hands back the one already in progress.
     *
     * Resuming rather than refusing is the point: a student whose connection
     * dropped reopens the page and continues, with the clock still running
     * from the original started_at. That is what makes the answers they had
     * already saved worth having.
     */
    public function handle(Exam $exam, User $student): ExamAttempt
    {
        if (! $exam->status->acceptsSubmissions()) {
            throw ExamWorkflowException::notOpen();
        }

        $existing = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->first();

        if ($existing !== null) {
            if ($existing->isSubmitted()) {
                throw ExamWorkflowException::alreadySubmitted();
            }

            return $existing;
        }

        return $this->create($exam, $student);
    }

    private function create(Exam $exam, User $student): ExamAttempt
    {
        $total = $exam->questions()->count();

        if ($total === 0) {
            throw ExamWorkflowException::noQuestions();
        }

        try {
            return DB::transaction(fn () => ExamAttempt::create([
                'exam_id' => $exam->id,
                'user_id' => $student->id,
                // Server clock, never the browser's.
                'started_at' => now(),
                'total_questions' => $total,
                'status' => AttemptStatus::InProgress,
            ]));
        } catch (QueryException $e) {
            // Two tabs opened the exam at the same moment. The unique index on
            // (exam_id, user_id) is what actually prevents a second attempt;
            // this turns the collision into the row the other tab just made.
            $attempt = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $student->id)
                ->first();

            if ($attempt === null) {
                throw $e;
            }

            return $attempt;
        }
    }
}
