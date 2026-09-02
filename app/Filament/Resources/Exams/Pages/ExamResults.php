<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Models\ExamAttempt;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What a class actually scored.
 *
 * The exit criterion for M4 is that a teacher sees marks without asking anyone
 * for them, so this reads straight from stored results and computes nothing a
 * student could influence.
 */
class ExamResults extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ExamResource::class;

    protected string $view = 'filament.resources.exams.pages.exam-results';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // Results carry other students' marks, so this is narrower than being
        // allowed to see the exam itself.
        abort_unless(auth()->user()?->can('viewResults', $this->record) ?? false, 403);
    }

    public function getTitle(): string
    {
        return 'Nilai: '.$this->record->title;
    }

    /** @return Collection<int, ExamAttempt> */
    public function attempts(): Collection
    {
        return ExamAttempt::query()
            ->where('exam_id', $this->record->id)
            ->with(['student:id,name,classroom_id', 'student.classroom:id,name'])
            ->orderByRaw('score DESC NULLS LAST')
            ->get();
    }

    /** @return array{peserta: int, dinilai: int, rata: string|null, tertinggi: string|null, terendah: string|null} */
    public function stats(): array
    {
        $graded = ExamAttempt::query()
            ->where('exam_id', $this->record->id)
            ->ranked()
            ->selectRaw('count(*) as jumlah, avg(score) as rata, max(score) as tertinggi, min(score) as terendah')
            ->first();

        return [
            'peserta' => ExamAttempt::where('exam_id', $this->record->id)->count(),
            'dinilai' => (int) ($graded->jumlah ?? 0),
            'rata' => $graded?->rata === null ? null : number_format((float) $graded->rata, 2, ',', '.'),
            'tertinggi' => $graded?->tertinggi === null ? null : number_format((float) $graded->tertinggi, 2, ',', '.'),
            'terendah' => $graded?->terendah === null ? null : number_format((float) $graded->terendah, 2, ',', '.'),
        ];
    }

    /**
     * The questions this class got wrong most often, so a teacher knows what to
     * go over again -- a user story in docs/01-PRD.md.
     *
     * One grouped query on the query builder rather than Eloquent: these are
     * aggregates, not Question records.
     *
     * @return list<array{stem: string, dijawab: int, benar: int, persen: int}>
     */
    public function hardestQuestions(int $limit = 5): array
    {
        return DB::table('attempt_answers')
            ->join('exam_attempts', 'exam_attempts.id', '=', 'attempt_answers.exam_attempt_id')
            ->join('questions', 'questions.id', '=', 'attempt_answers.question_id')
            ->where('exam_attempts.exam_id', $this->record->id)
            ->whereNotNull('attempt_answers.is_correct')
            ->groupBy('questions.id', 'questions.stem')
            ->selectRaw('questions.stem as stem')
            ->selectRaw('count(*) as dijawab')
            ->selectRaw('sum(case when attempt_answers.is_correct then 1 else 0 end) as benar')
            ->orderByRaw('sum(case when attempt_answers.is_correct then 1 else 0 end)::float / count(*) asc')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => [
                'stem' => (string) $row->stem,
                'dijawab' => (int) $row->dijawab,
                'benar' => (int) $row->benar,
                'persen' => (int) round((int) $row->benar / max(1, (int) $row->dijawab) * 100),
            ])
            ->all();
    }
}
