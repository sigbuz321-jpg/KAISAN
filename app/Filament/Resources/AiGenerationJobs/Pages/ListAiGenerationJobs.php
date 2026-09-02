<?php

namespace App\Filament\Resources\AiGenerationJobs\Pages;

use App\Actions\RequestQuestionGeneration;
use App\Enums\DifficultyBand;
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
            Select::make('subject_id')
                ->label('Mata pelajaran')
                ->options(fn () => Subject::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
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

            TextInput::make('grade')
                ->label('Jenjang kelas')
                ->numeric()
                ->minValue(1)
                ->maxValue(12)
                ->helperText('Boleh dikosongkan. Membantu AI menyesuaikan bahasa dengan usia murid.'),
        ];
    }

    /** @param array<string, mixed> $data */
    private function request(array $data): void
    {
        $subject = Subject::findOrFail($data['subject_id']);
        $topic = $data['topic_id'] ? Topic::find($data['topic_id']) : null;

        try {
            app(RequestQuestionGeneration::class)->handle(
                auth()->user(),
                $subject,
                $topic,
                DifficultyBand::from($data['difficulty']),
                (int) $data['count'],
                $data['grade'] === null || $data['grade'] === '' ? null : (int) $data['grade'],
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
}
