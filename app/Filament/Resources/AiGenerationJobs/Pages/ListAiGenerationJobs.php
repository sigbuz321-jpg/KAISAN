<?php

namespace App\Filament\Resources\AiGenerationJobs\Pages;

use App\Actions\RequestQuestionGeneration;
use App\Enums\DifficultyBand;
use App\Enums\SchoolLevel;
use App\Exceptions\AiQuotaException;
use App\Filament\Resources\AiGenerationJobs\AiGenerationJobResource;
use App\Models\AiGenerationJob;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;

class ListAiGenerationJobs extends ListRecords
{
    protected static string $resource = AiGenerationJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buatSoalAi')
                ->label('Buat soal dengan AI')
                ->icon('heroicon-o-sparkles')
                ->modalHeading('Buat soal dengan AI')
                ->modalDescription('Soal yang dibuat AI selalu masuk sebagai "Menunggu tinjauan". Anda yang memutuskan mana yang layak diterbitkan.')
                ->modalSubmitActionLabel('Buat soal')
                ->schema(self::formSchema())
                ->action(fn (array $data) => $this->request($data)),
        ];
    }

    /** @return array<int, mixed> */
    private static function formSchema(): array
    {
        $max = (int) config('kaisan.ai.max_questions_per_job', AiGenerationJob::MAX_QUESTIONS_PER_JOB);

        return [
            Select::make('school_level')
                ->label('Jenjang sekolah')
                ->placeholder('Pilih jenjang sekolah...')
                ->options(SchoolLevel::options())
                ->native(false)
                ->live()
                ->afterStateUpdated(function (callable $set) {
                    $set('subject_id', null);
                    $set('topic_id', null);
                    $set('grade', null);
                }),

            TextInput::make('grade')
                ->label('Kelas')
                ->numeric()
                ->minValue(fn (callable $get) => match ($get('school_level')) {
                    SchoolLevel::SD->value => 1,
                    SchoolLevel::SMP->value => 7,
                    default => 1,
                })
                ->maxValue(fn (callable $get) => match ($get('school_level')) {
                    SchoolLevel::SD->value => 6,
                    SchoolLevel::SMP->value => 9,
                    default => 12,
                })
                ->helperText(fn (callable $get) => match ($get('school_level')) {
                    SchoolLevel::SD->value => 'Kelas 1 sampai 6 untuk tingkat SD.',
                    SchoolLevel::SMP->value => 'Kelas 7, 8, atau 9 untuk tingkat SMP.',
                    default => 'Boleh dikosongkan. Pilih jenjang terlebih dahulu untuk batasan kelas.',
                })
                ->live(),

            Select::make('subject_id')
                ->label('Mata pelajaran')
                ->options(function (callable $get) {
                    $level = $get('school_level');
                    $grade = $get('grade');

                    return Subject::query()
                        ->where('is_active', true)
                        ->when($level, fn ($q) => $q->where('school_level', $level))
                        ->when(filled($grade), fn ($q) => $q->forGrade((int) $grade))
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Subject $s) => [$s->id => $s->nameOnly()])
                        ->all();
                })
                ->required()
                ->searchable()
                ->native(false)
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('topic_id', null)),

            Select::make('topic_id')
                ->label('Bab')
                ->options(fn (callable $get) => $get('subject_id')
                    ? Topic::query()->where('subject_id', $get('subject_id'))->orderBy('order')->pluck('name', 'id')->all()
                    : [])
                ->searchable()
                ->native(false)
                ->helperText('Boleh dikosongkan. Tanpa bab, soal dibuat untuk mata pelajaran secara umum.'),

            Select::make('difficulty')
                ->label('Tingkat kesulitan')
                ->options(DifficultyBand::options())
                ->default(DifficultyBand::Medium->value)
                ->required()
                ->native(false),

            TextInput::make('count')
                ->label('Jumlah soal')
                ->numeric()
                ->default(5)
                ->minValue(1)
                ->maxValue($max)
                ->required()
                ->helperText("Paling banyak {$max} soal sekali minta. Setiap soal menambah biaya pemakaian AI."),
        ];
    }

    /**
     * Validate that grade sits inside the selected school level, then dispatch.
     *
     * @param  array<string, mixed>  $data
     */
    private function request(array $data): void
    {
        $level = $data['school_level'] ?? null;
        $gradeRaw = $data['grade'] ?? null;
        $grade = $gradeRaw === null || $gradeRaw === '' ? null : (int) $gradeRaw;

        if ($grade !== null) {
            if ($level === SchoolLevel::SD->value && ($grade < 1 || $grade > 6)) {
                $this->reject('Kelas untuk jenjang SD hanya kelas 1 sampai 6.');
            }

            if ($level === SchoolLevel::SMP->value && ($grade < 7 || $grade > 9)) {
                $this->reject('Kelas untuk jenjang SMP hanya kelas 7, 8, atau 9.');
            }
        }

        $subject = Subject::findOrFail($data['subject_id']);
        $topic = $data['topic_id'] ? Topic::find($data['topic_id']) : null;

        try {
            app(RequestQuestionGeneration::class)->handle(
                auth()->user(),
                $subject,
                $topic,
                DifficultyBand::from($data['difficulty']),
                (int) $data['count'],
                $grade,
            );
        } catch (AiQuotaException $e) {
            Notification::make()
                ->title('Permintaan tidak bisa diproses')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        // The router is called by a worker, so there is nothing to show yet.
        // Saying so plainly stops teachers pressing the button again.
        Notification::make()
            ->title('Permintaan diterima')
            ->body('Soal sedang dibuat di latar belakang. Anda akan diberi tahu saat selesai, biasanya dalam satu sampai dua menit.')
            ->success()
            ->send();
    }

    private function reject(string $message): never
    {
        Notification::make()
            ->title('Permintaan tidak bisa diproses')
            ->body($message)
            ->danger()
            ->send();

        // Livewire action callers expect a thrown exception; redirect-back flashes
        // the validation error rather than silently swallowing it.
        throw ValidationException::withMessages(['grade' => $message]);
    }
}
