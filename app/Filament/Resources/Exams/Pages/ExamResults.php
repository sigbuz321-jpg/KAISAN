<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Enums\AttemptStatus;
use App\Filament\Resources\Exams\ExamResource;
use App\Models\Exam;
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
        abort_unless(auth()->user()?->can('viewResults', $this->exam()) ?? false, 403);
    }

    public function getTitle(): string
    {
        return 'Nilai: '.$this->exam()->title;
    }

    /** @return Collection<int, ExamAttempt> */
    public function attempts(): Collection
    {
        return ExamAttempt::query()
            ->where('exam_id', $this->exam()->id)
            ->with(['student:id,name,classroom_id', 'student.classroom:id,name'])
            ->orderByRaw('score DESC NULLS LAST')
            ->get();
    }

    /**
     * Averages ignore voided results but the list still shows them: rule 6 of
     * domain-kaisan.md keeps the record and only stops it counting.
     *
     * On the query builder rather than Eloquent, because these are aggregates
     * and not ExamAttempt records.
     *
     * @return array{peserta: int, dinilai: int, rata: string|null, tertinggi: string|null, terendah: string|null}
     */
    public function stats(): array
    {
        $row = DB::table('exam_attempts')
            ->where('exam_id', $this->exam()->id)
            ->whereNotNull('submitted_at')
            ->whereNull('voided_at')
            ->selectRaw('count(*) as jumlah, avg(score) as rata, max(score) as tertinggi, min(score) as terendah')
            ->first();

        return [
            'peserta' => ExamAttempt::where('exam_id', $this->exam()->id)->count(),
            'dinilai' => (int) ($row->jumlah ?? 0),
            'rata' => self::angka($row?->rata),
            'tertinggi' => self::angka($row?->tertinggi),
            'terendah' => self::angka($row?->terendah),
        ];
    }

    /**
     * The questions this class got wrong most often, so a teacher knows what to
     * go over again -- a user story in docs/01-PRD.md.
     *
     * @return list<array{stem: string, dijawab: int, benar: int, persen: int}>
     */
    public function hardestQuestions(int $limit = 5): array
    {
        return DB::table('attempt_answers')
            ->join('exam_attempts', 'exam_attempts.id', '=', 'attempt_answers.exam_attempt_id')
            ->join('questions', 'questions.id', '=', 'attempt_answers.question_id')
            ->where('exam_attempts.exam_id', $this->exam()->id)
            ->where('exam_attempts.status', AttemptStatus::Submitted->value)
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

    /** The record, typed. InteractsWithRecord widens it to Model|int|string. */
    public function exam(): Exam
    {
        /** @var Exam $exam */
        $exam = $this->record;

        return $exam;
    }

    private static function angka(mixed $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, ',', '.');
    }
}
