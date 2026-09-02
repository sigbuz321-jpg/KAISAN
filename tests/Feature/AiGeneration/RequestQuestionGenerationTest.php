<?php

use App\Actions\RequestQuestionGeneration;
use App\Enums\AiJobStatus;
use App\Enums\DifficultyBand;
use App\Exceptions\AiQuotaException;
use App\Jobs\GenerateQuestionsJob;
use App\Models\AiGenerationJob;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->guru = User::factory()->guru()->create();
    $this->subject = Subject::factory()->create();
    $this->action = app(RequestQuestionGeneration::class);
});

it('queues the work instead of calling the router in the request', function () {
    $record = $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 5);

    expect($record->status)->toBe(AiJobStatus::Queued)
        ->and($record->requested_by)->toBe($this->guru->id)
        ->and($record->count)->toBe(5);

    Queue::assertPushed(GenerateQuestionsJob::class,
        fn (GenerateQuestionsJob $job) => $job->aiGenerationJobId === $record->id);
});

it('keeps the requested grade for the prompt without storing it as a column', function () {
    $record = $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Easy, 3, grade: 8);

    expect($record->meta)->toBe(['grade' => 8]);
});

it('refuses more questions than one job may ask for', function () {
    config(['kaisan.ai.max_questions_per_job' => 20]);

    expect(fn () => $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 21))
        ->toThrow(AiQuotaException::class, 'paling banyak 20 soal');

    expect(AiGenerationJob::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('refuses a request for no questions at all', function () {
    expect(fn () => $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 0))
        ->toThrow(AiQuotaException::class);
});

it('stops a teacher who has hit the hourly job limit', function () {
    config(['kaisan.ai.jobs_per_hour' => 3]);

    for ($i = 0; $i < 3; $i++) {
        $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 1);
    }

    expect(fn () => $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 1))
        ->toThrow(AiQuotaException::class, 'batas 3 permintaan');

    expect(AiGenerationJob::count())->toBe(3);
});

it('counts the hourly limit per teacher, not across the whole school', function () {
    config(['kaisan.ai.jobs_per_hour' => 2]);

    $other = User::factory()->guru()->create();

    $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 1);
    $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 1);

    // The other teacher's quota is untouched.
    $record = $this->action->handle($other, $this->subject, null, DifficultyBand::Medium, 1);

    expect($record->requested_by)->toBe($other->id);
});

it('lets a teacher through again once the hour has passed', function () {
    config(['kaisan.ai.jobs_per_hour' => 1]);

    $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 1);

    $this->travel(61)->minutes();

    $record = $this->action->handle($this->guru, $this->subject, null, DifficultyBand::Medium, 1);

    expect($record->status)->toBe(AiJobStatus::Queued);
});
