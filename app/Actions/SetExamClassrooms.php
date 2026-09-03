<?php

namespace App\Actions;

use App\Exceptions\ExamWorkflowException;
use App\Models\Exam;

class SetExamClassrooms
{
    /**
     * Sets which classes sit an exam.
     *
     * Frozen once the exam leaves draft, for the same reason the questions are:
     * a student may already have seen it, and adding a class halfway through
     * would hand them a paper their classmates started an hour earlier.
     *
     * @param  list<int>  $classroomIds
     */
    public function handle(Exam $exam, array $classroomIds): int
    {
        if (! $exam->status->allowsQuestionEditing()) {
            throw ExamWorkflowException::classroomsAreFrozen();
        }

        $exam->classrooms()->sync($classroomIds);

        return $exam->classrooms()->count();
    }
}
