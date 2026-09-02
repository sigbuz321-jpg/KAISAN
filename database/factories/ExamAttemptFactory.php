<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExamAttempt> */
class ExamAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory()->active(),
            'user_id' => User::factory()->murid(),
            'started_at' => now(),
            'total_questions' => 5,
            'status' => AttemptStatus::InProgress,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
            'score' => '80.00',
            'correct_count' => 4,
        ]);
    }

    public function voided(string $reason = 'Terindikasi menyontek.'): static
    {
        return $this->submitted()->state(fn () => [
            'status' => AttemptStatus::Voided,
            'voided_at' => now(),
            'voided_reason' => $reason,
        ]);
    }
}
