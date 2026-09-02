<?php

use App\Enums\AiJobStatus;
use App\Enums\DifficultyBand;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Jobs\GenerateQuestionsJob;
use App\Models\AiGenerationJob;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.ai_router.url' => 'https://router.test/v1',
        'services.ai_router.key' => 'test-key',
        'services.ai_router.model' => 'test-model',
        'kaisan.ai.price_per_million.prompt' => 3.0,
        'kaisan.ai.price_per_million.completion' => 15.0,
    ]);

    $this->guru = User::factory()->guru()->create();
    $this->subject = Subject::factory()->create(['name' => 'Matematika']);
});

function generationJob(array $attributes = []): AiGenerationJob
{
    return AiGenerationJob::factory()->create(array_merge([
        'requested_by' => test()->guru->id,
        'subject_id' => test()->subject->id,
        'count' => 2,
    ], $attributes));
}

/** Runs the job in-process, letting the container supply its collaborators. */
function runGeneration(AiGenerationJob $record): void
{
    app()->call([new GenerateQuestionsJob($record->id), 'handle']);
}

it('stores generated questions for review', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload([
        aiQuestion('Berapakah hasil dari 12 dikali 12?'),
        aiQuestion('Berapakah akar kuadrat dari 144?'),
    ]))]);

    runGeneration(generationJob());

    expect(Question::count())->toBe(2)
        ->and(Question::where('status', QuestionStatus::Review)->count())->toBe(2)
        ->and(Question::where('source', QuestionSource::Ai)->count())->toBe(2);
});

it('marks the record done and records what it cost', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload(
        [aiQuestion('Berapakah hasil dari 12 dikali 12?')],
        promptTokens: 1_000,
        completionTokens: 2_000,
    ))]);

    $record = generationJob(['count' => 1]);

    runGeneration($record);

    $record->refresh();

    // 1000 prompt tokens at 3.00 and 2000 completion tokens at 15.00 per
    // million: 0.003 + 0.030.
    expect($record->status)->toBe(AiJobStatus::Done)
        ->and($record->model)->toBe('test-model')
        ->and($record->prompt_tokens)->toBe(1_000)
        ->and($record->completion_tokens)->toBe(2_000)
        ->and($record->estimated_cost)->toBe('0.0330')
        ->and($record->finished_at)->not->toBeNull();
});

it('gives an AI question the Elo difficulty of the requested band', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload([aiQuestion()]))]);

    runGeneration(generationJob(['count' => 1, 'difficulty' => DifficultyBand::Hard]));

    expect(Question::sole()->difficulty)->toBe(DifficultyBand::Hard->toElo());
});

it('keeps the good questions when one candidate has only three options', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload([
        aiQuestion('Berapakah hasil dari 12 dikali 12?'),
        aiQuestion('Soal yang cacat karena kurang satu pilihan', [
            'options' => ['A' => 'satu', 'B' => 'dua', 'C' => 'tiga'],
        ]),
    ]))]);

    $record = generationJob();

    runGeneration($record);

    expect(Question::count())->toBe(1)
        ->and($record->refresh()->savedCount())->toBe(1)
        ->and($record->rejectedCount())->toBe(1)
        ->and($record->status)->toBe(AiJobStatus::Done);
});

it('rejects a candidate whose answer key is outside A to D', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload([
        aiQuestion('Soal dengan kunci jawaban di luar rentang', ['answer_key' => 'E']),
    ]))]);

    $record = generationJob(['count' => 1]);

    runGeneration($record);

    expect(Question::count())->toBe(0)
        ->and($record->refresh()->rejectedCount())->toBe(1);
});

it('rejects a candidate with two identical options', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload([
        aiQuestion('Soal dengan dua pilihan yang kembar', [
            'options' => ['A' => 'empat', 'B' => 'Empat', 'C' => 'lima', 'D' => 'enam'],
        ]),
    ]))]);

    runGeneration(generationJob(['count' => 1]));

    expect(Question::count())->toBe(0);
});

it('skips a question the subject already has', function () {
    $existing = Question::factory()->create([
        'subject_id' => $this->subject->id,
        'stem' => 'Berapakah hasil dari 12 dikali 12?',
        'created_by' => $this->guru->id,
    ]);

    Http::fake(['router.test/*' => Http::response(aiRouterPayload([
        // Same wording, different spacing and case: the normalised hash catches it.
        aiQuestion('  berapakah HASIL dari 12   dikali 12?  '),
    ]))]);

    $record = generationJob(['count' => 1]);

    runGeneration($record);

    expect(Question::count())->toBe(1)
        ->and(Question::sole()->id)->toBe($existing->id)
        ->and($record->refresh()->duplicateCount())->toBe(1);
});

it('retries once with a sharpened prompt when the reply is not JSON', function () {
    Http::fakeSequence('router.test/*')
        ->push(aiRouterPayload('Tentu! Ini soalnya: bukan JSON sama sekali.'))
        ->push(aiRouterPayload([aiQuestion('Berapakah hasil dari 12 dikali 12?')]));

    $record = generationJob(['count' => 1]);

    runGeneration($record);

    expect(Question::count())->toBe(1)
        ->and($record->refresh()->status)->toBe(AiJobStatus::Done);

    Http::assertSentCount(2);
});

it('fails with a message a teacher can act on when the reply stays unreadable', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload('masih bukan JSON'))]);

    $record = generationJob(['count' => 1]);

    runGeneration($record);

    $record->refresh();

    expect($record->status)->toBe(AiJobStatus::Failed)
        ->and($record->error)->toBe('Gagal membuat soal. Silakan coba lagi atau ubah topiknya.')
        ->and($record->finished_at)->not->toBeNull();
});

it('leaves no half-written questions behind when it fails', function () {
    Http::fake(['router.test/*' => Http::response(aiRouterPayload('bukan JSON'))]);

    runGeneration(generationJob(['count' => 5]));

    expect(Question::count())->toBe(0);
});

it('lets the queue retry when the router cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $record = generationJob(['count' => 1]);

    // A transient fault must surface so the worker backs off and tries again,
    // rather than being swallowed as a permanent failure.
    expect(fn () => runGeneration($record))
        ->toThrow(App\Exceptions\AiRouterException::class);

    expect($record->refresh()->status)->toBe(AiJobStatus::Running);
});

it('does nothing when the record has already finished', function () {
    Http::fake();

    $record = generationJob(['count' => 1]);
    $record->update(['status' => AiJobStatus::Done]);

    runGeneration($record);

    expect(Question::count())->toBe(0);
    Http::assertNothingSent();
});

it('never sends student data to the router', function () {
    $murid = User::factory()->murid()->create(['name' => 'Budi Santoso']);
    $topic = Topic::factory()->create(['subject_id' => $this->subject->id, 'name' => 'Perkalian']);

    Http::fake(['router.test/*' => Http::response(aiRouterPayload([aiQuestion()]))]);

    $record = generationJob(['count' => 1, 'topic_id' => $topic->id]);

    runGeneration($record);

    Http::assertSent(function ($request) use ($murid) {
        $body = json_encode($request->data());

        return str_contains($body, 'Matematika')
            && str_contains($body, 'Perkalian')
            && ! str_contains($body, $murid->name)
            && ! str_contains($body, $murid->email);
    });
});
