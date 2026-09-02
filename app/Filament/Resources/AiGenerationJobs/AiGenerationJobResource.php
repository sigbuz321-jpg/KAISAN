<?php

namespace App\Filament\Resources\AiGenerationJobs;

use App\Filament\Resources\AiGenerationJobs\Pages\ListAiGenerationJobs;
use App\Filament\Resources\AiGenerationJobs\Tables\AiGenerationJobsTable;
use App\Models\AiGenerationJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AiGenerationJobResource extends Resource
{
    protected static ?string $model = AiGenerationJob::class;

    protected static ?string $navigationLabel = 'Permintaan Soal AI';

    protected static ?string $modelLabel = 'Permintaan soal AI';

    protected static ?string $pluralModelLabel = 'Permintaan soal AI';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return AiGenerationJobsTable::configure($table);
    }

    /**
     * A teacher sees only what they asked for. Hiding rows in the UI is not
     * enough on its own -- AiGenerationJobPolicy enforces the same rule -- but
     * a teacher should not be shown a list they cannot open either.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['subject', 'topic', 'requester']);

        $user = auth()->user();

        if ($user !== null && ! $user->isAdmin()) {
            $query->where('requested_by', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiGenerationJobs::route('/'),
        ];
    }
}
