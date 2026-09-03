<?php

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Season;
use Illuminate\View\View;

class UjianController extends Controller
{
    /**
     * The exams this student can see, and where they stand with each.
     *
     * Two queries plus an eager load, whatever the number of exams: the
     * attempts are fetched once and matched in memory rather than looked up
     * inside the loop.
     */
    public function index(): View
    {
        $student = auth()->user();

        abort_unless($student->isMurid(), 403, 'Halaman ini untuk murid.');

        $season = Season::current();

        $exams = $season === null || $student->classroom_id === null
            ? collect()
            : Exam::query()
                ->visibleToStudents()
                ->where('season_id', $season->id)
                // Only exams this student's class was actually given. Without
                // this every student saw every exam in the school.
                ->whereHas('classrooms', fn ($q) => $q->whereKey($student->classroom_id))
                ->with('subject')
                ->orderByDesc('starts_at')
                ->get();

        $attempts = ExamAttempt::query()
            ->where('user_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('exam_id');

        return view('murid.ujian.index', [
            'exams' => $exams,
            'attempts' => $attempts,
        ]);
    }
}
