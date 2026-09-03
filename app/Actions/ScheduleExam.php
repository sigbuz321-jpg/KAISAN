<?php

namespace App\Actions;

use App\Enums\ExamStatus;
use App\Exceptions\ExamWorkflowException;
use App\Models\Exam;

class ScheduleExam
{
    /**
     * Hands a draft exam over to the schedule.
     *
     * After this the questions are frozen and students can see the exam is
     * coming. The scheduled command opens and closes it from here on; nothing
     * a teacher does moves it directly into `active`.
     */
    public function handle(Exam $exam): Exam
    {
        if ($exam->status !== ExamStatus::Draft) {
            throw ExamWorkflowException::questionsAreFrozen();
        }

        $attached = $exam->questions()->count();

        // An exam with no questions would open, run its full duration and grade
        // everyone zero. Caught here rather than discovered by a class.
        if ($attached === 0) {
            throw ExamWorkflowException::noQuestions();
        }

        // And an exam with no classes would open for nobody, which is a
        // half-finished exam rather than a scheduled one.
        if ($exam->classrooms()->count() === 0) {
            throw ExamWorkflowException::noClassrooms();
        }

        $exam->update([
            'question_count' => $attached,
            'status' => ExamStatus::Scheduled,
        ]);

        return $exam;
    }
}
