<?php

namespace App\Services\AiRouter;

use App\Models\AiGenerationJob;
use Illuminate\Support\Str;

/**
 * Builds the generation prompt.
 *
 * This class is the single place where text leaves for an external service, so
 * it is deliberately the only thing that decides what goes in. Per
 * .claude/rules/security.md it may name a subject, a topic, a grade level, a
 * difficulty and a count -- nothing else. It never receives a student record,
 * and it never reads one.
 */
class QuestionPromptBuilder
{
    public function build(AiGenerationJob $job): string
    {
        $lines = [
            "Buat {$job->count} soal pilihan ganda Bahasa Indonesia.",
            'Mata pelajaran: '.$job->subject->name,
        ];

        if ($job->topic !== null) {
            $lines[] = 'Topik: '.$job->topic->name;
        }

        $grade = $job->meta['grade'] ?? null;

        if ($grade !== null) {
            $lines[] = 'Jenjang: kelas '.$grade;
        }

        $lines[] = 'Tingkat kesulitan: '.$job->difficulty->promptWord();

        return implode("\n", $lines)."\n\n".$this->rules();
    }

    /** Sent after a reply we could not parse, with the formatting demand sharpened. */
    public function buildRetry(AiGenerationJob $job): string
    {
        return $this->build($job)
            ."\n\nPENTING: balasan sebelumnya tidak bisa dibaca. Balas HANYA array JSON "
            .'yang valid. Jangan gunakan pagar kode, jangan tambahkan teks apa pun sebelum '
            .'atau sesudah array.';
    }

    /**
     * Identical requests inside the cache window reuse a reply instead of
     * paying the router again -- teachers press the button twice.
     */
    public function cacheKey(AiGenerationJob $job): string
    {
        return 'ai:questions:'.hash('sha256', implode('|', [
            $job->subject_id,
            $job->topic_id ?? '-',
            $job->difficulty->value,
            $job->count,
            $job->meta['grade'] ?? '-',
            Str::of(config('services.ai_router.model') ?? '')->toString(),
        ]));
    }

    private function rules(): string
    {
        return <<<'TXT'
        Aturan:
        - Tepat 4 opsi: A, B, C, D
        - Tepat satu jawaban benar
        - Pengecoh harus masuk akal, bukan asal salah
        - Tanpa gambar, tanpa tabel
        - Sertakan pembahasan singkat 1-2 kalimat

        Balas HANYA dengan array JSON. Tanpa markdown, tanpa penjelasan tambahan.
        [{"stem":"...","options":{"A":"...","B":"...","C":"...","D":"..."},"answer_key":"A","explanation":"..."}]
        TXT;
    }
}
