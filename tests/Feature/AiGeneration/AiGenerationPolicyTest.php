<?php

use App\Models\AiGenerationJob;
use App\Models\User;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->admin = User::factory()->admin()->create();
    $this->murid = User::factory()->murid()->create();
});

it('lets a teacher open their own generation record', function () {
    $record = AiGenerationJob::factory()->create(['requested_by' => $this->guru->id]);

    expect($this->guru->can('view', $record))->toBeTrue();
});

it('hides another teacher generation record', function () {
    $other = User::factory()->guru()->create();
    $record = AiGenerationJob::factory()->create(['requested_by' => $other->id]);

    expect($this->guru->can('view', $record))->toBeFalse();
});

it('lets the admin see every generation record, because the admin pays for them', function () {
    $record = AiGenerationJob::factory()->create(['requested_by' => $this->guru->id]);

    expect($this->admin->can('view', $record))->toBeTrue();
});

it('keeps students out of generation records entirely', function () {
    $record = AiGenerationJob::factory()->create(['requested_by' => $this->guru->id]);

    expect($this->murid->can('view', $record))->toBeFalse()
        ->and($this->murid->can('viewAny', AiGenerationJob::class))->toBeFalse()
        ->and($this->murid->can('create', AiGenerationJob::class))->toBeFalse();
});

it('shows the cost recap to the admin only', function () {
    expect($this->admin->can('viewCostReport', AiGenerationJob::class))->toBeTrue()
        ->and($this->guru->can('viewCostReport', AiGenerationJob::class))->toBeFalse();
});

it('never lets anyone edit or delete a billing record', function () {
    $record = AiGenerationJob::factory()->create(['requested_by' => $this->guru->id]);

    expect($this->admin->can('update', $record))->toBeFalse()
        ->and($this->admin->can('delete', $record))->toBeFalse()
        ->and($this->guru->can('delete', $record))->toBeFalse();
});
