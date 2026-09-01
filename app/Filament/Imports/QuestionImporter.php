<?php

namespace App\Filament\Imports;

use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use RuntimeException;

/**
 * Bulk question intake. Everything lands as a draft written by the importing
 * teacher: a spreadsheet must never be able to put a question straight in front
 * of students without somebody reading it first.
 */
class QuestionImporter extends Importer
{
    protected static ?string $model = Question::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('subject')
                ->label('Mata pelajaran')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->fillRecordUsing(function (Question $record, string $state): void {
                    $subject = Subject::query()->where('name', trim($state))->first();

                    if ($subject === null) {
                        throw new RuntimeException("Mata pelajaran \"{$state}\" belum ada. Buat dulu di menu Mata Pelajaran.");
                    }

                    $record->subject_id = $subject->id;
                }),

            ImportColumn::make('topic')
                ->label('Bab')
                ->fillRecordUsing(function (Question $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $topic = Topic::query()
                        ->where('subject_id', $record->subject_id)
                        ->where('name', trim($state))
                        ->first();

                    if ($topic === null) {
                        throw new RuntimeException("Bab \"{$state}\" tidak ada pada mata pelajaran itu.");
                    }

                    $record->topic_id = $topic->id;
                }),

            ImportColumn::make('stem')
                ->label('Batang soal')
                ->requiredMapping()
                ->rules(['required', 'string']),

            // The four choices are columns in the file but one jsonb column in
            // the table, so they must not be written onto the model directly --
            // questions has no option_a column. fillRecord() folds them in.
            ...collect(Question::OPTION_KEYS)->map(fn (string $key) => ImportColumn::make('option_'.strtolower($key))
                ->label('Pilihan '.$key)
                ->requiredMapping()
                ->rules(['required', 'string', 'max:500'])
                ->fillRecordUsing(fn () => null))->all(),

            ImportColumn::make('answer_key')
                ->label('Kunci jawaban')
                ->requiredMapping()
                ->rules(['required', 'in:A,B,C,D'])
                ->fillRecordUsing(fn (Question $record, string $state) => $record->answer_key = strtoupper(trim($state))),

            // Optional columns arrive as empty strings, not nulls, when a
            // spreadsheet leaves them blank. Passing '' to an integer column
            // aborts the whole transaction, so both are normalised here.
            ImportColumn::make('explanation')
                ->label('Pembahasan')
                ->fillRecordUsing(fn (Question $record, ?string $state) => $record->explanation = blank($state) ? null : trim($state)),

            ImportColumn::make('difficulty')
                ->label('Tingkat kesulitan')
                ->rules(['nullable', 'integer', 'between:400,2400'])
                ->fillRecordUsing(function (Question $record, ?string $state): void {
                    if (filled($state)) {
                        $record->difficulty = (int) $state;
                    }
                }),
        ];
    }

    public function resolveRecord(): Question
    {
        $subject = Subject::query()->where('name', trim((string) $this->data['subject']))->first();

        // Same wording in the same subject is the same question, so a second
        // upload of a corrected file updates instead of colliding with the
        // unique (subject_id, stem_hash) index.
        $existing = $subject === null ? null : Question::query()
            ->where('subject_id', $subject->id)
            ->where('stem_hash', Question::hashStem((string) $this->data['stem']))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $question = new Question;
        $question->status = QuestionStatus::Draft;
        $question->source = QuestionSource::Manual;
        $question->created_by = $this->import->user_id;
        $question->difficulty = 1200;

        return $question;
    }

    /**
     * Filament 4 has no afterFill hook -- fillRecord and saveRecord are the
     * only seams -- so the four option columns are folded into the jsonb
     * column here, after the mapped columns have been applied.
     */
    public function fillRecord(): void
    {
        parent::fillRecord();

        $record = $this->record;

        if (! $record instanceof Question) {
            return;
        }

        $record->options = [
            'A' => $this->data['option_a'],
            'B' => $this->data['option_b'],
            'C' => $this->data['option_c'],
            'D' => $this->data['option_d'],
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor selesai. '.number_format($import->successful_rows).' soal masuk sebagai draf.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failed).' baris gagal dan bisa diunduh untuk diperbaiki.';
        }

        return $body;
    }
}
