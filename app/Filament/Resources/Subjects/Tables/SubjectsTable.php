<?php

namespace App\Filament\Resources\Subjects\Tables;

use App\Enums\SchoolLevel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('school_level')
                    ->label('Jenjang')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Umum')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value) {
                        'sd' => 'info',
                        'smp' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('grade_range')
                    ->label('Rentang kelas')
                    ->getStateUsing(fn ($record) => $record->gradeRangeLabel() ?? '—'),
                TextColumn::make('topics_count')->label('Jumlah bab')->counts('topics'),
                TextColumn::make('questions_count')->label('Jumlah soal')->counts('questions'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('school_level')
                    ->label('Jenjang')
                    ->options(SchoolLevel::options()),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()->label('Ubah'),
                // SubjectPolicy blocks this while any question still hangs off it.
                DeleteAction::make()->label('Hapus'),
            ])
            ->emptyStateHeading('Belum ada mata pelajaran')
            ->emptyStateDescription('Buat mata pelajaran lebih dulu sebelum menulis soal.');
    }
}
