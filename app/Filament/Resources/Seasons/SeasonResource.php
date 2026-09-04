<?php

namespace App\Filament\Resources\Seasons;

use App\Filament\Resources\Seasons\Pages\ListSeasons;
use App\Filament\Resources\Seasons\Tables\SeasonsTable;
use App\Models\Season;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;

    protected static ?string $navigationLabel = 'Musim';

    protected static ?string $modelLabel = 'Musim';

    protected static ?string $pluralModelLabel = 'Musim';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return SeasonsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeasons::route('/'),
        ];
    }
}
