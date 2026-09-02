<?php

namespace Database\Factories;

use App\Enums\AiJobStatus;
use App\Enums\DifficultyBand;
use App\Models\AiGenerationJob;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiGenerationJob> */
class AiGenerationJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requested_by' => User::factory()->guru(),
            'subject_id' => Subject::factory(),
            'topic_id' => null,
            'difficulty' => DifficultyBand::Medium,
            'count' => 5,
            'status' => AiJobStatus::Queued,
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => ['status' => AiJobStatus::Running]);
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => AiJobStatus::Done,
            'model' => 'test-model',
            'prompt_tokens' => 400,
            'completion_tokens' => 900,
            'estimated_cost' => '0.0130',
            'finished_at' => now(),
            'meta' => ['saved' => 5, 'rejected' => 0, 'duplicates' => 0],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => AiJobStatus::Failed,
            'error' => 'Gagal membuat soal. Silakan coba lagi atau ubah topiknya.',
            'finished_at' => now(),
        ]);
    }
}
