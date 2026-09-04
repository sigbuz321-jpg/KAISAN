<?php

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardEntry;
use App\Models\Season;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PeringkatController extends Controller
{
    /** Long enough to absorb a rush of refreshes, short enough to feel live. */
    private const CACHE_SECONDS = 60;

    /**
     * The standings, read straight from the table the scheduled job writes.
     *
     * Nothing is computed here. With 500 students this would otherwise be the
     * slowest query in the application, run once per page view.
     */
    public function index(Request $request): View
    {
        $student = $request->user();

        abort_unless($student->isMurid(), 403, 'Halaman ini untuk murid.');

        $season = Season::current();
        $subjects = Subject::query()->where('is_active', true)->orderBy('name')->get();

        $subjectId = $request->integer('mapel') ?: null;

        if ($subjectId !== null && ! $subjects->contains('id', $subjectId)) {
            $subjectId = null;
        }

        [$top, $mine] = $season === null
            ? [collect(), null]
            : $this->board($season, $subjectId, $student->id);

        return view('murid.peringkat.index', [
            'season' => $season,
            'subjects' => $subjects,
            'subjectId' => $subjectId,
            'top' => $top,
            'mine' => $mine,
        ]);
    }

    /**
     * @return array{0: Collection<int, LeaderboardEntry>, 1: LeaderboardEntry|null}
     */
    private function board(Season $season, ?int $subjectId, int $studentId): array
    {
        $key = "peringkat:{$season->id}:".($subjectId ?? 'gabungan');

        $top = Cache::remember($key, self::CACHE_SECONDS, fn () => LeaderboardEntry::query()
            ->where('season_id', $season->id)
            ->when($subjectId === null,
                fn ($q) => $q->whereNull('subject_id'),
                fn ($q) => $q->where('subject_id', $subjectId))
            ->with('student:id,name')
            ->orderBy('rank')
            ->limit(20)
            ->get());

        // The student's own row is fetched separately and never cached per
        // student: they need to see themselves even when far outside the top.
        $mine = LeaderboardEntry::query()
            ->where('season_id', $season->id)
            ->when($subjectId === null,
                fn ($q) => $q->whereNull('subject_id'),
                fn ($q) => $q->where('subject_id', $subjectId))
            ->where('user_id', $studentId)
            ->first();

        return [$top, $mine];
    }
}
