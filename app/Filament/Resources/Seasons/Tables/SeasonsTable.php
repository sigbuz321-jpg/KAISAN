<?php

namespace App\Filament\Resources\Seasons\Tables;

use App\Models\LeaderboardEntry;
use App\Models\Season;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeasonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama musim')->searchable(),

                IconColumn::make('is_active')->label('Berjalan')->boolean(),

                TextColumn::make('starts_at')->label('Mulai')->dateTime('d M Y')->sortable(),
                TextColumn::make('ends_at')->label('Berakhir')->dateTime('d M Y')->placeholder('Masih berjalan'),

                TextColumn::make('exams_count')->label('Ujian')->counts('exams')->alignEnd(),

                // The frozen standings of a season that has ended: last
                // season's champions stay on the record.
                TextColumn::make('juara')
                    ->label('Peringkat 1 (gabungan)')
                    ->state(fn (Season $record) => LeaderboardEntry::query()
                        ->where('season_id', $record->id)
                        ->whereNull('subject_id')
                        ->where('rank', 1)
                        ->with('student:id,name')
                        ->get()
                        ->pluck('student.name')
                        ->join(', ') ?: '-'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->emptyStateHeading('Belum ada musim')
            ->emptyStateDescription('Mulai musim pertama untuk membuka peringkat.');
    }
}
