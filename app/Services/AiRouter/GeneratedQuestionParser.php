<?php

namespace App\Services\AiRouter;

use App\Exceptions\AiRouterException;
use JsonException;

/**
 * Turns the router's reply into an array of raw question candidates.
 *
 * Parsing only. Whether a candidate is usable is GeneratedQuestionValidator's
 * job, so a reply that is valid JSON but full of nonsense still parses here.
 */
class GeneratedQuestionParser
{
    /** @return list<array<string, mixed>> */
    public function parse(string $raw): array
    {
        $clean = $this->stripFences($raw);

        if ($clean === '') {
            throw AiRouterException::unreadableResponse();
        }

        try {
            $decoded = json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw AiRouterException::unreadableResponse();
        }

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            throw AiRouterException::unreadableResponse();
        }

        return array_values(array_filter($decoded, is_array(...)));
    }

    /**
     * Models like to wrap JSON in a ```json fence despite being asked not to,
     * and to add a sentence before it. Take the outermost array if one is there.
     */
    private function stripFences(string $raw): string
    {
        $clean = trim(preg_replace('/^\s*```(?:json)?|```\s*$/m', '', trim($raw)) ?? '');

        $start = strpos($clean, '[');
        $end = strrpos($clean, ']');

        if ($start !== false && $end !== false && $end > $start) {
            return substr($clean, $start, $end - $start + 1);
        }

        return $clean;
    }
}
