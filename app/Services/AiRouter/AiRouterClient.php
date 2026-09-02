<?php

namespace App\Services\AiRouter;

use App\Exceptions\AiRouterException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The only class that talks to the AI router.
 *
 * Credentials are read through config() rather than env() so a cached config
 * does not silently blank them in production -- see .claude/rules/security.md.
 */
class AiRouterClient
{
    public function complete(string $prompt): AiRouterResponse
    {
        $url = config('services.ai_router.url');
        $key = config('services.ai_router.key');
        $model = config('services.ai_router.model');

        if (! is_string($url) || $url === '' || ! is_string($key) || $key === '') {
            throw AiRouterException::notConfigured();
        }

        $model = is_string($model) && $model !== '' ? $model : 'default';

        try {
            $response = Http::withToken($key)
                ->timeout((int) config('kaisan.ai.timeout', 120))
                ->acceptJson()
                ->asJson()
                ->post(rtrim($url, '/').'/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    // The contract is a bare JSON array; creativity here costs
                    // the client money in rejected questions.
                    'temperature' => 0.7,
                ]);
        } catch (ConnectionException) {
            throw AiRouterException::unreachable();
        }

        if ($response->failed()) {
            // Status only. The body may echo the prompt back, and the headers
            // carry the key.
            Log::warning('ai router returned an error', ['status' => $response->status()]);

            throw AiRouterException::unreachable();
        }

        $payload = $response->json();

        return AiRouterResponse::fromArray(is_array($payload) ? $payload : [], $model);
    }
}
