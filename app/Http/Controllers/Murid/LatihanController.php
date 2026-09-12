<?php

namespace App\Http\Controllers\Murid;

use App\Enums\QuestionStatus;
use App\Http\Controllers\Controller;
use App\Models\StudentAbility;
use App\Models\Subject;
use Illuminate\View\View;

class LatihanController extends Controller
{
    /**
     * The subjects a student can practise, with where they stand in each.
     *
     * Subjects with an empty question bank are left out entirely. This reverses
     * an earlier decision to show them as "belum ada soal": Kurikulum Merdeka
     * defines around eleven subjects per school level and a bimbel teaches only
     * a few, so showing them all buried the two or three a student could
     * actually open under a wall of dead ends.
     */
    public function index(): View
    {
        $student = auth()->user();

        abort_unless($student->isMurid(), 403, 'Halaman ini untuk murid.');

        $subjects = Subject::query()
            ->visibleTo($student)
            ->withPublishedQuestions()
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
