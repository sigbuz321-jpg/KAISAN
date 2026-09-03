<?php

namespace App\Http\Controllers\Murid;

use App\Enums\QuestionStatus;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\StudentAbility;
use App\Models\Subject;
use Illuminate\View\View;

class LatihanController extends Controller
{
    /**
     * The subjects a student can practise, with where they stand in each.
     *
     * A subject with no published questions is shown but not offered: better
     * to say the bank is empty than to hand a student a screen that cannot
     * give them anything.
     */
    public function index(): View
    {
        $student = auth()->user();

        abort_unless($student->isMurid(), 403, 'Halaman ini untuk murid.');

        $subjects = Subject::query()
            ->where('is_active', true)
            ->withCount(['questions as published_questions_count' => fn ($q) => $q->where('status', QuestionStatus::Published)])
            ->orderBy('name')
            ->get();

        // One query for every subject rather than one per row.
        $abilities = StudentAbility::query()
            ->where('user_id', $student->id)
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy('subject_id');

        return view('murid.latihan.index', [
            'subjects' => $subjects,
            'abilities' => $abilities,
            'startingRating' => StudentAbility::startingRating(),
        ]);
    }
}
