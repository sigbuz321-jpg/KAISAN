<?php

namespace App\Services\AiRouter;

/**
 * Estimates what one call cost, from the token counts the router reported.
 *
 * This is an estimate on purpose. The router's own invoice stays the source of
 * truth; this number exists so the client can see a running total in the panel
 * and question a bill that looks wrong, per .claude/rules/domain-kaisan.md.
 */
class CostEstimator
{
    private const PER_MILLION = 1_000_000;

    public function estimate(int $promptTokens, int $completionTokens): string
    {
        $promptRate = (float) config('kaisan.ai.price_per_million.prompt', 0);
        $completionRate = (float) config('kaisan.ai.price_per_million.completion', 0);

        $cost = ($promptTokens * $promptRate + $completionTokens * $completionRate) / self::PER_MILLION;

        // Stored in numeric(12,4), so round here rather than letting the
        // database truncate silently.
        return number_format($cost, 4, '.', '');
    }
}
