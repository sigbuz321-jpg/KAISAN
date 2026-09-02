<?php

use App\Enums\AiJobStatus;
use App\Enums\DifficultyBand;
use App\Filament\Pages\AiCostReport;
use App\Filament\Resources\AiGenerationJobs\AiGenerationJobResource;
use App\Filament\Resources\AiGenerationJobs\Pages\ListAiGenerationJobs;
use App\Jobs\GenerateQuestionsJob;
use App\Models\AiGenerationJob;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->guru = User::factory()->guru()->create();
    $this->admin = User::factory()->admin()->create();
    $this->subject = Subject::factory()->create();
});

it('lets a teacher open the AI request list', function () {
    $this->actingAs($this->guru)
        ->get(AiGenerationJobResource::getUrl('index'))
        ->assertOk();
});

it('keeps students out of the AI request list', function () {
    $this->actingAs(User::factory()->murid()->create())
        ->get(AiGenerationJobResource::getUrl('index'))
        ->assertForbidden();
});

it('shows a teacher only their own requests', function () {
    $other = User::factory()->guru()->create();

    $mine = AiGenerationJob::factory()->create([
        'requested_by' => $this->guru->id,
        'subject_id' => $this->subject->id,
    ]);
    $theirs = AiGenerationJob::factory()->create([
        'requested_by' => $other->id,
        'subject_id' => $this->subject->id,
    ]);

    $this->actingAs($this->guru);

    Livewire::test(ListAiGenerationJobs::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('shows the admin every teacher request', function () {
    $mine = AiGenerationJob::factory()->create([
        'requested_by' => $this->guru->id,
        'subject_id' => $this->subject->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListAiGenerationJobs::class)->assertCanSeeTableRecords([$mine]);
});

it('queues a request made from the panel', function () {
    Queue::fake();

    $this->actingAs($this->guru);

    Livewire::test(ListAiGenerationJobs::class)
        ->callAction('buatSoalAi', data: [
            'subject_id' => $this->subject->id,
            'topic_id' => null,
            'difficulty' => DifficultyBand::Hard->value,
            'count' => 4,
            'grade' => 9,
        ]);

    $record = AiGenerationJob::sole();

    expect($record->requested_by)->toBe($this->guru->id)
        ->and($record->count)->toBe(4)
        ->and($record->difficulty)->toBe(DifficultyBand::Hard)
        ->and($record->status)->toBe(AiJobStatus::Queued)
        ->and($record->meta)->toBe(['grade' => 9]);

    Queue::assertPushed(GenerateQuestionsJob::class);
});

it('refuses a panel request that breaks the hourly limit', function () {
    Queue::fake();
    config(['kaisan.ai.jobs_per_hour' => 1]);

    AiGenerationJob::factory()->create([
        'requested_by' => $this->guru->id,
        'subject_id' => $this->subject->id,
    ]);

    $this->actingAs($this->guru);

    Livewire::test(ListAiGenerationJobs::class)
        ->callAction('buatSoalAi', data: [
            'subject_id' => $this->subject->id,
            'topic_id' => null,
            'difficulty' => DifficultyBand::Medium->value,
            'count' => 2,
            'grade' => null,
        ]);

    // Unchanged: the guard ran before anything was written or queued.
    expect(AiGenerationJob::count())->toBe(1);
    Queue::assertNothingPushed();
});

it('opens the cost report for the admin', function () {
    $this->actingAs($this->admin)
        ->get(AiCostReport::getUrl())
        ->assertOk();
});

it('hides the cost report from a teacher', function () {
    $this->actingAs($this->guru)
        ->get(AiCostReport::getUrl())
        ->assertForbidden();
});

it('adds up the monthly spend on the cost report', function () {
    AiGenerationJob::factory()->done()->count(3)->create([
        'requested_by' => $this->guru->id,
        'subject_id' => $this->subject->id,
        'estimated_cost' => '1.5000',
    ]);

    $this->actingAs($this->admin);

    $page = new AiCostReport;

    expect($page->currentMonthCost())->toBe(4.5)
        ->and($page->rows()[0]['jobs'])->toBe(3)
        // The done() factory state records five saved questions per job.
        ->and($page->rows()[0]['questions'])->toBe(15);
});
