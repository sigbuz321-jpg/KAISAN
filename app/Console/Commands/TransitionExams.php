<?php

namespace App\Console\Commands;

use App\Actions\GradeExamAttempt;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Walks exams through their lifecycle, once a minute.
 *
 * Statuses are stored, not derived. Working them out inside a request would
 * mean 150 students refreshing an exam page each recomputing the same thing,
 * and would leave the answer to "is this exam open?" depending on who asked.
 */
class TransitionExams extends Command
{
    protected $signature = 'exams:transition';

    protected $description = 'Buka, tutup, dan nilai ujian sesuai jadwalnya';

    public function handle(GradeExamAttempt $grade, ExamWindow $window): int
    {
        $opened = $this->open();
        $closed = $this->close();
        $graded = $this->grade($grade, $window);

        $this->info("Dibuka: {$opened}, ditutup: {$closed}, dinilai: {$graded}.");

        return self::SUCCESS;
    }

    /** One statement: there is nothing per-exam to decide here. */
    private function open(): int
    {
        return Exam::query()
            ->where('status', ExamStatus::Scheduled)
            ->where('starts_at', '<=', now())
            ->update(['status' => ExamStatus::Active]);
    }

    private function close(): int
    {
        return Exam::query()
            ->where('status', ExamStatus::Active)
            ->where('ends_at', '<', now())
            ->update(['status' => ExamStatus::Closed]);
    }

    /**
     * Grades every closed exam, then marks it graded.
     *
     * Attempts still open when the window shut are submitted as they stand,
     * with whatever was saved. A student whose connection died keeps the work
     * they did -- product decision, and the PRD promise that answers are not
     * lost when the connection drops would be hollow otherwise. Unanswered
     * questions still count as wrong.
     */
    private function grade(GradeExamAttempt $grade, ExamWindow $window): int
    {
        $count = 0;

        Exam::query()
            ->where('status', ExamStatus::Closed)
            ->with('season')
            ->chunkById(50, function ($exams) use ($grade, $window, &$count) {
                foreach ($exams as $exam) {
                    $this->gradeHangingAttempts($exam, $grade, $window);

                    $exam->update(['status' => ExamStatus::Graded]);
                    $count++;
                }
            });

        return $count;
    }

    private function gradeHangingAttempts(Exam $exam, GradeExamAttempt $grade, ExamWindow $window): void
    {
        ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->inProgress()
            ->with('exam')
            ->chunkById(100, function ($attempts) use ($grade, $window) {
                foreach ($attempts as $attempt) {
                    // Stamped with the moment their own time ran out, not with
                    // "now": the record should say when the work stopped being
                    // possible, not when this command happened to run.
                    $grade->handle($attempt, $window->deadlineFor($attempt));

                    Log::info('attempt auto-submitted at close', ['attempt_id' => $attempt->id]);
                }
            });
    }
}
