<?php

use App\Enums\AiJobStatus;
use App\Filament\Guru\Widgets\GuruOverview;
use App\Models\AiGenerationJob;
use App\Models\User;

/**
 * The monthly recap is the owner's business, not a teacher's
 * (AiGenerationJobPolicy::viewCostReport). The dashboard widget must not become
 * a second, unguarded way to read it.
 */
it('shows an admin the whole month of AI spend', function () {
    $admin = User::factory()->admin()->create();

    AiGenerationJob::factory()->create([
        'requested_by' => User::factory()->guru()->create()->id,
        'status' => AiJobStatus::Done,
        'estimated_cost' => 40,
    ]);

    $this->actingAs($admin);

    expect((new GuruOverview)->biayaAiBulanIni())->toBe(40.0)
        ->and((new GuruOverview)->bolehLihatTotalBiaya())->toBeTrue();
});

it('scopes AI spend on the dashboard to the teacher who asked', function () {
    $guru = User::factory()->guru()->create();
    $lain = User::factory()->guru()->create();

    AiGenerationJob::factory()->create([
        'requested_by' => $guru->id,
        'status' => AiJobStatus::Done,
        'estimated_cost' => 10,
    ]);

    AiGenerationJob::factory()->create([
        'requested_by' => $lain->id,
        'status' => AiJobStatus::Done,
        'estimated_cost' => 90,
    ]);

    $this->actingAs($guru);

    expect((new GuruOverview)->biayaAiBulanIni())->toBe(10.0);
});

it('keeps the monthly budget away from a teacher', function () {
    config(['services.ai_router.monthly_budget' => 100000]);

    $this->actingAs(User::factory()->guru()->create());
    expect((new GuruOverview)->batasBiayaAi())->toBeNull();

    $this->actingAs(User::factory()->admin()->create());
    expect((new GuruOverview)->batasBiayaAi())->toBe(100000.0);
});
