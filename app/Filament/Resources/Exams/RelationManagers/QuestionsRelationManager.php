<?php

namespace App\Filament\Resources\Exams\RelationManagers;

use App\Actions\PickRandomQuestions;
use App\Actions\SetExamQuestions;
use App\Enums\DifficultyBand;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Topic;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Soal ujian';

    protected static ?string $modelLabel = 'Soal';

    public function table(Table $table): Table
    {
        /** @var Exam $exam */
        $exam = $this->getOwnerRecord();

        $editable = $exam->status->allowsQuestionEditing();

        return $table
            ->columns([
                TextColumn::make('nomor')
                    ->label('No')
                    ->state(function (Question $record): int {
                        /** @var Pivot|null $pivot */
                        $pivot = $record->getAttribute('pivot');

                        return (int) ($pivot?->getAttribute('order') ?? 0) + 1;
                    })
                    ->alignEnd(),

                TextColumn::make('stem')->label('Soal')->limit(80)->wrap()->searchable(),
                TextColumn::make('topic.name')->label('Bab')->placeholder('-'),
                TextColumn::make('difficulty')->label('Kesulitan')->alignEnd()->toggleable(),
            ])
            ->defaultSort('exam_questions.order')
            ->headerActions([
                // Not Filament's AttachAction: exam_questions.order is NOT NULL
                // and attach() would not fill it. Going through
                // SetExamQuestions also keeps the draft-only rule and the
                // question_count in one place.
                Action::make('pilih')
                    ->label('Pilih soal')
                    ->icon('heroicon-o-list-bullet')
                    ->visible($editable)
                    ->modalHeading('Pilih soal untuk ujian')
                    ->modalDescription('Hanya soal yang sudah terbit di mata pelajaran ini yang bisa dipakai.')
                    ->modalSubmitActionLabel('Simpan pilihan')
                    ->schema([
                        Select::make('questions')
                            ->label('Soal')
                            ->multiple()
                            ->searchable()
                            ->options(fn () => Question::query()
                                ->where('subject_id', $exam->subject_id)
                                ->where('status', QuestionStatus::Published)
                                ->orderBy('id')
                                ->pluck('stem', 'id')
                                ->map(fn (string $stem) => Str::limit($stem, 90))
                                ->all())
                            ->default(fn () => $exam->questions()->pluck('questions.id')->all())
                            ->helperText('Urutan mengikuti urutan pilihan. Kalau pengacakan soal aktif, tiap murid tetap mendapat urutan berbeda.'),
                    ])
                    ->action(function (array $data) use ($exam) {
                        $saved = app(SetExamQuestions::class)->handle(
                            $exam,
                            array_map(intval(...), $data['questions'] ?? []),
                        );

                        Notification::make()
                            ->title("{$saved} soal disimpan untuk ujian ini.")
                            ->success()
                            ->send();
                    }),

                Action::make('acak')
                    ->label('Ambil soal acak')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->visible($editable)
                    ->modalHeading('Ambil soal acak')
                    ->modalDescription('Soal yang sudah dipilih akan diganti dengan hasil pengambilan acak.')
                    ->modalSubmitActionLabel('Ambil soal')
                    ->schema([
                        TextInput::make('jumlah')
                            ->label('Jumlah soal')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),

                        Select::make('topic_id')
                            ->label('Bab')
                            ->options(fn () => Topic::query()
                                ->where('subject_id', $exam->subject_id)
                                ->orderBy('order')
                                ->pluck('name', 'id')
                                ->all())
                            ->native(false)
                            ->helperText('Kosongkan untuk mengambil dari semua bab.'),

                        Select::make('difficulty')
                            ->label('Tingkat kesulitan')
                            ->options(DifficultyBand::options())
                            ->native(false)
                            ->helperText('Kosongkan untuk semua tingkat.'),
                    ])
                    ->action(function (array $data) use ($exam) {
                        $asked = (int) $data['jumlah'];

                        $taken = app(PickRandomQuestions::class)->handle(
                            $exam,
                            $asked,
                            $data['topic_id'] ? (int) $data['topic_id'] : null,
                            $data['difficulty'] ? DifficultyBand::from($data['difficulty']) : null,
                        );

                        Notification::make()
                            ->title("{$taken} soal dimasukkan ke ujian.")
                            // Said plainly rather than quietly handing the
                            // teacher a shorter paper than they asked for.
                            ->body($taken < $asked
                                ? 'Bank soal belum punya cukup soal terbit yang cocok dengan pilihan itu.'
                                : null)
                            ->color($taken < $asked ? 'warning' : 'success')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('pratinjau')
                    ->label('Pratinjau')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Tampilan di layar murid')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Question $record) => view('components.question-preview', [
                        'question' => $record,
                        'showAnswer' => true,
                    ])),
            ])
            ->emptyStateHeading($editable ? 'Belum ada soal' : 'Ujian ini tidak punya soal')
            ->emptyStateDescription($editable
                ? 'Pilih soal satu per satu, atau ambil sejumlah soal secara acak.'
                : null);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('update', $ownerRecord) ?? false;
    }
}
