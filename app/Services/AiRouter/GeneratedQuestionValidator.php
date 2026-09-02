<?php

namespace App\Services\AiRouter;

use App\Models\Question;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Screens raw candidates from the router before anything reaches the database.
 *
 * Nothing here touches the database: duplicates against questions already
 * stored are caught in StoreGeneratedQuestions, which owns the transaction.
 * This class only judges a candidate on its own merits and against the rest of
 * its own batch.
 */
class GeneratedQuestionValidator
{
    /** @param list<array<string, mixed>> $candidates */
    public function validate(array $candidates): ValidationResult
    {
        $accepted = [];
        $reasons = [];
        $seen = [];

        foreach ($candidates as $index => $candidate) {
            $position = $index + 1;
            $failure = $this->firstFailure($candidate);

            if ($failure !== null) {
                $reasons[] = "Soal ke-{$position}: {$failure}";

                continue;
            }

            /** @var array<string, string> $options */
            $options = $candidate['options'];
            $stem = Str::of((string) $candidate['stem'])->squish()->toString();
            $fingerprint = Question::hashStem($stem);

            if (isset($seen[$fingerprint])) {
                $reasons[] = "Soal ke-{$position}: sama dengan soal lain dalam permintaan yang sama.";

                continue;
            }

            $seen[$fingerprint] = true;

            $accepted[] = [
                'stem' => $stem,
                'options' => array_map(
                    fn (string $text) => Str::of($text)->squish()->toString(),
                    $options
                ),
                'answer_key' => (string) $candidate['answer_key'],
                'explanation' => Str::of((string) ($candidate['explanation'] ?? ''))->squish()->toString(),
            ];
        }

        return new ValidationResult($accepted, $reasons);
    }

    /** @param array<string, mixed> $candidate */
    private function firstFailure(array $candidate): ?string
    {
        $validator = Validator::make($candidate, [
            'stem' => ['required', 'string', 'min:10', 'max:1000'],
            'options' => ['required', 'array', 'size:4'],
            'options.A' => ['required', 'string', 'max:500'],
            'options.B' => ['required', 'string', 'max:500'],
            'options.C' => ['required', 'string', 'max:500'],
            'options.D' => ['required', 'string', 'max:500'],
            'answer_key' => ['required', 'string', 'in:A,B,C,D'],
            'explanation' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return 'format dari AI tidak sesuai.';
        }

        /** @var array<string, string> $options */
        $options = $candidate['options'];

        return $this->semanticFailure($options, (string) $candidate['answer_key']);
    }

    /** @param array<string, string> $options */
    private function semanticFailure(array $options, string $answerKey): ?string
    {
        $normalised = array_map(
            fn (string $text) => Str::of($text)->lower()->squish()->toString(),
            $options
        );

        if (in_array('', $normalised, true)) {
            return 'ada pilihan jawaban yang kosong.';
        }

        // A distractor identical to the key gives the question two right
        // answers, which is worse than having no question at all.
        if (count(array_unique($normalised)) !== count($normalised)) {
            return 'ada dua pilihan jawaban yang sama.';
        }

        if (! array_key_exists($answerKey, $options)) {
            return 'kunci jawaban menunjuk pilihan yang tidak ada.';
        }

        return null;
    }
}
