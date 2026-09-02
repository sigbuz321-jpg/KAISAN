<?php

namespace App\Jobs;

use App\Actions\StoreGeneratedQuestions;
use App\Enums\AiJobStatus;
use App\Exceptions\AiRouterException;
use App\Models\AiGenerationJob;
use App\Services\AiRouter\AiRouterClient;
use App\Services\AiRouter\AiRouterResponse;
use App\Services\AiRouter\CostEstimator;
use App\Services\AiRouter\GeneratedQuestionParser;
use App\Services\AiRouter\GeneratedQuestionValidator;
use App\Services\AiRouter\QuestionPromptBuilder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asks the AI router for questions and files the results for review.
 *
 * Unique on the generation record, so a worker restarting mid-flight cannot
 * produce the batch twice.
 */
class GenerateQuestionsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries;

    public function __construct(public readonly int $aiGenerationJobId)
    {
        $this->tries = (int) config('kaisan.ai.tries', 3);
    }

    public function uniqueId(): string
    {
        return (string) $this->aiGenerationJobId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(
        AiRouterClient $client,
        QuestionPromptBuilder $prompts,
        GeneratedQuestionParser $parser,
        GeneratedQuestionValidator $validator,
        CostEstimator $costs,
        StoreGeneratedQuestions $store,
    ): void {
        // The prompt names the subject and topic, and lazy loading is an error
        // outside production.
        $record = AiGenerationJob::with(['subject', 'topic'])->find($this->aiGenerationJobId);

        // Already handled, or the record was deleted underneath us.
        if ($record === null || $record->status->isFinished()) {
            return;
        }

        $record->update(['status' => AiJobStatus::Running]);

        try {
            $response = $this->ask($client, $prompts, $record);
            $candidates = $this->parseWithOneRetry($client, $prompts, $parser, $record, $response);
        } catch (AiRouterException $e) {
            if ($e->retryable) {
                throw $e;
            }

            $this->markFailed($record, $e->getMessage());

            return;
        }

        $result = $validator->validate($candidates);
        $stored = $store->handle($record, $result->accepted);

        $record->forceFill([
            'status' => AiJobStatus::Done,
            'model' => $response->model,
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'estimated_cost' => $costs->estimate($response->promptTokens, $response->completionTokens),
            'finished_at' => now(),
            'meta' => array_merge($record->meta ?? [], [
                'saved' => $stored['saved'],
                'duplicates' => $stored['duplicates'],
                'rejected' => $result->rejectedCount(),
                'reasons' => $result->reasons,
            ]),
        ])->save();

        Log::info('ai generation finished', [
            'ai_generation_job_id' => $record->id,
            'saved' => $stored['saved'],
        ]);
    }

    /**
     * An identical request inside the cache window reuses the previous reply.
     * Teachers press the button twice; the client should not pay twice.
     */
    private function ask(
        AiRouterClient $client,
        QuestionPromptBuilder $prompts,
        AiGenerationJob $record,
    ): AiRouterResponse {
        /** @var AiRouterResponse $response */
        $response = Cache::remember(
            $prompts->cacheKey($record),
            (int) config('kaisan.ai.cache_ttl', 86400),
            fn () => $client->complete($prompts->build($record)),
        );

        return $response;
    }

    /**
     * Models sometimes answer with prose or broken JSON. One sharpened retry is
     * worth the money; a second is not.
     *
     * @return list<array<string, mixed>>
     */
    private function parseWithOneRetry(
        AiRouterClient $client,
        QuestionPromptBuilder $prompts,
        GeneratedQuestionParser $parser,
        AiGenerationJob $record,
        AiRouterResponse &$response,
    ): array {
        try {
            return $parser->parse($response->content);
        } catch (AiRouterException) {
            Log::warning('ai reply could not be parsed, retrying once', [
                'ai_generation_job_id' => $record->id,
            ]);
        }

        // Deliberately not cached: the cached reply is the one that just failed.
        $response = $client->complete($prompts->buildRetry($record));

        return $parser->parse($response->content);
    }

    public function failed(?Throwable $e): void
    {
        $record = AiGenerationJob::find($this->aiGenerationJobId);

        if ($record === null || $record->status->isFinished()) {
            return;
        }

        $this->markFailed($record, $e instanceof AiRouterException
            ? $e->getMessage()
            : 'Gagal membuat soal. Silakan coba lagi atau ubah topiknya.');
    }

    private function markFailed(AiGenerationJob $record, string $message): void
    {
        $record->forceFill([
            'status' => AiJobStatus::Failed,
            'error' => $message,
            'finished_at' => now(),
        ])->save();

        Log::warning('ai generation failed', ['ai_generation_job_id' => $record->id]);
    }
}
