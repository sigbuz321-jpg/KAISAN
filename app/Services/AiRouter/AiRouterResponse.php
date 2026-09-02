<?php

namespace App\Services\AiRouter;

/** What the router returned, reduced to the four things this project needs. */
readonly class AiRouterResponse
{
    public function __construct(
        public string $content,
        public string $model,
        public int $promptTokens,
        public int $completionTokens,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  decoded OpenAI-compatible body
     */
    public static function fromArray(array $payload, string $fallbackModel): self
    {
        /** @var array<string, mixed> $usage */
        $usage = is_array($payload['usage'] ?? null) ? $payload['usage'] : [];

        return new self(
            content: self::extractContent($payload),
            model: is_string($payload['model'] ?? null) ? $payload['model'] : $fallbackModel,
            promptTokens: (int) ($usage['prompt_tokens'] ?? 0),
            completionTokens: (int) ($usage['completion_tokens'] ?? 0),
        );
    }

    /** @param array<string, mixed> $payload */
    private static function extractContent(array $payload): string
    {
        $content = data_get($payload, 'choices.0.message.content');

        return is_string($content) ? $content : '';
    }
}
