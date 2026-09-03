<?php

namespace App\Actions;

use App\Models\PracticeSession;
use App\Models\StudentAbility;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StartPracticeSession
{
    /**
     * Opens a practice sitting, resuming one already open for this subject.
     *
     * Resuming matters because a student who closes the tab and comes back
     * should continue the same session rather than fragment their history into
     * dozens of one-question sittings.
     */
    public function handle(User $student, Subject $subject): PracticeSession
    {
        return DB::transaction(function () use ($student, $subject) {
            $this->abilityFor($student, $subject);

            $open = PracticeSession::query()
                ->where('user_id', $student->id)
                ->where('subject_id', $subject->id)
                ->whereNull('ended_at')
                ->first();

            return $open ?? PracticeSession::create([
                'user_id' => $student->id,
                'subject_id' => $subject->id,
                'started_at' => now(),
            ]);
        });
    }

    /** Every student starts a subject at the same rating; see EloRating::START. */
    public function abilityFor(User $student, Subject $subject): StudentAbility
    {
        return StudentAbility::firstOrCreate(
            ['user_id' => $student->id, 'subject_id' => $subject->id],
            ['rating' => StudentAbility::startingRating()],
        );
    }
}
