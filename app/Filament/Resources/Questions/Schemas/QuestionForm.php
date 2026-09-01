<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Penempatan')
                ->schema([
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
                        ->helperText('Boleh dikosongkan.'),

                    TextInput::make('difficulty')
                        ->label('Tingkat kesulitan')
                        ->numeric()
                        ->default(1200)
                        ->minValue(400)
                        ->maxValue(2400)
                        ->helperText('Skala Elo. 1200 berarti sedang; sistem menyesuaikannya sendiri seiring murid menjawab.'),
                ])
                ->columns(2),

            Section::make('Isi soal')
                ->schema([
                    Textarea::make('stem')
                        ->label('Batang soal')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Tulis pertanyaannya saja, tanpa pilihan jawaban.'),

                    TextInput::make('options.A')->label('Pilihan A')->required()->maxLength(500),
                    TextInput::make('options.B')->label('Pilihan B')->required()->maxLength(500),
                    TextInput::make('options.C')->label('Pilihan C')->required()->maxLength(500),
                    TextInput::make('options.D')->label('Pilihan D')->required()->maxLength(500),

                    Select::make('answer_key')
                        ->label('Kunci jawaban')
                        ->options(array_combine(Question::OPTION_KEYS, Question::OPTION_KEYS))
                        ->required()
                        ->native(false),

                    Textarea::make('explanation')
                        ->label('Pembahasan')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Ditampilkan ke murid setelah menjawab saat latihan. Tidak pernah muncul selama ujian.'),
                ])
                ->columns(2),
        ]);
    }
}
