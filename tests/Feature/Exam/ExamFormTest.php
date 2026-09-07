<?php

use App\Enums\ExamStatus;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\Exams\Pages\CreateExam;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Season;
use App\Models\Subject;
use App\Models\User;
use Livewire\Livewire;

/**
 * Reported from the deployed instance: a teacher pressing "Buat" on the exam
 * form got an error dialog.
 *
 * The record Filament creates carries no status in the form data, so the
 * in-memory model reported null for it -- the column default only exists in
 * the database. The form then asked that record whether its questions were
 * still editable and died with "Call to a member function
 * allowsQuestionEditing() on null".
 */
it('opens the create exam form for a teacher', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get(ExamResource::getUrl('create', panel: 'guru'))
        ->assertOk();
});

it('creates an exam from the form without erroring', function () {
    $guru = User::factory()->guru()->create();
    $subject = Subject::factory()->create();
    $kelas = Classroom::factory()->create();
    $season = Season::current() ?? Season::factory()->active()->create();

    Livewire::actingAs($guru)
        ->test(CreateExam::class)
        ->fillForm([
            'title' => 'Ulangan Harian Aljabar',
            'subject_id' => $subject->id,
            'season_id' => $season->id,
            'classrooms' => [$kelas->id],
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'difficulty_weight' => '1.00',
            'shuffle_questions' => true,
            'shuffle_options' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $exam = Exam::where('title', 'Ulangan Harian Aljabar')->first();

    expect($exam)->not->toBeNull()
        ->and($exam->status)->toBe(ExamStatus::Draft)
        ->and($exam->classrooms)->toHaveCount(1);
});
