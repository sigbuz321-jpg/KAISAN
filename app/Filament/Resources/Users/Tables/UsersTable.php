<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use App\Enums\SchoolLevel;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (Role $state) => $state->label()),

                TextColumn::make('school_level')
                    ->label('Jenjang')
                    ->badge()
                    ->formatStateUsing(fn (?SchoolLevel $state, User $record) => $record->effectiveSchoolLevel()?->label() ?? '-')
                    ->placeholder('-'),

                TextColumn::make('grade')
                    ->label('Kelas')
                    ->formatStateUsing(fn (?int $state, User $record) => $record->effectiveGrade() ? "Kelas {$record->effectiveGrade()}" : '-')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('classroom.name')
                    ->label('Rombel')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('last_login_at')
                    ->label('Terakhir masuk')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum pernah')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('school_level')
                    ->label('Jenjang')
                    ->options(SchoolLevel::options()),

                SelectFilter::make('grade')
                    ->label('Kelas')
                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $g) => [$g => "Kelas {$g}"])->all()),

                SelectFilter::make('classroom_id')
                    ->label('Rombel')
                    ->relationship('classroom', 'name'),

                SelectFilter::make('role')
                    ->label('Peran')
                    ->options(Role::options())
                    ->visible(fn () => auth()->user()?->isAdmin()),

                TernaryFilter::make('is_active')
                    ->label('Status akun')
                    ->trueLabel('Hanya aktif')
                    ->falseLabel('Hanya nonaktif'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()->label('Ubah'),

                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription('Riwayat nilai dan pengerjaan ujian tetap tersimpan.')
                    ->visible(fn (User $record) => auth()->user()?->can('deactivate', $record) ?? false)
                    ->action(fn (User $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->emptyStateHeading('Belum ada akun');
    }
}
