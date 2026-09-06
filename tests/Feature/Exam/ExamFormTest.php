<?php

use App\Filament\Resources\Exams\ExamResource;
use App\Models\User;

/**
 * Reported from the deployed instance: a teacher pressing "Buat" on the exam
 * form got an error dialog. The form asks the record for its status to decide
 * whether the participating classes are still editable, and a brand-new Exam
 * reported null for it.
 */
it('opens the create exam form for a teacher', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get(ExamResource::getUrl('create', panel: 'guru'))
        ->assertOk();
});

it('opens the create exam form for an admin', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(ExamResource::getUrl('create'))
        ->assertOk();
});
