<?php

namespace Database\Seeders;

use App\Actions\ChangeQuestionStatus;
use App\Enums\AiJobStatus;
use App\Enums\DifficultyBand;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Models\AiGenerationJob;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'guru@kaisan.test')->firstOrFail();
        $changeStatus = app(ChangeQuestionStatus::class);

        foreach (self::questions() as $row) {
            [$subjectName, $topicName, $stem, $options, $key, $explanation, $source, $status] = $row;

            $subject = Subject::where('name', $subjectName)->firstOrFail();
            $topic = Topic::where('subject_id', $subject->id)->where('name', $topicName)->first();

            // Matched on the wording, not on stem_hash: the hash is filled by a
            // saving hook and is not mass assignable.
            $question = Question::updateOrCreate(
                ['subject_id' => $subject->id, 'stem' => $stem],
                [
                    'topic_id' => $topic?->id,
                    'stem' => $stem,
                    'options' => $options,
                    'answer_key' => $key,
                    'explanation' => $explanation,
                    'difficulty' => DifficultyBand::Medium->toElo(),
                    'source' => $source,
                    'status' => QuestionStatus::Draft,
                    'created_by' => $teacher->id,
                ],
            );

            // Moved through the real workflow rather than written straight to
            // the target status, so the approver stamp is filled in the same
            // way it would be in the panel.
            if ($status !== QuestionStatus::Draft && $question->status !== $status) {
                $changeStatus->handle($question, $status, $teacher);
            }
        }

        $this->generationHistory($teacher);
    }

    /**
     * One finished generation record, so the AI request list and the cost
     * recap have something in them on a fresh install.
     */
    private function generationHistory(User $teacher): void
    {
        $subject = Subject::where('name', 'Matematika')->firstOrFail();

        $job = AiGenerationJob::firstOrNew([
            'requested_by' => $teacher->id,
            'subject_id' => $subject->id,
            'difficulty' => DifficultyBand::Medium,
            'count' => 4,
        ]);

        $job->forceFill([
            'status' => AiJobStatus::Done,
            'model' => 'contoh-model',
            'prompt_tokens' => 620,
            'completion_tokens' => 1_480,
            'estimated_cost' => '0.0240',
            'finished_at' => now()->subHours(2),
            'meta' => ['grade' => 7, 'saved' => 3, 'rejected' => 1, 'duplicates' => 0, 'reasons' => [
                'Soal ke-4: format dari AI tidak sesuai.',
            ]],
        ])->save();
    }

    /**
     * Real wording rather than "Berapakah hasil dari 3 + 3?", because this is
     * the data the client is shown during a demo.
     *
     * AI-written rows are left waiting for review on purpose: it gives the
     * bulk approval screen something to act on straight after a reset.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: array<string, string>, 4: string, 5: string, 6: QuestionSource, 7: QuestionStatus}>
     */
    private static function questions(): array
    {
        $manual = QuestionSource::Manual;
        $ai = QuestionSource::Ai;
        $published = QuestionStatus::Published;
        $review = QuestionStatus::Review;
        $draft = QuestionStatus::Draft;

        return [
            ['Matematika', 'Bilangan Bulat', 'Hasil dari -8 + 15 adalah ...',
                ['A' => '7', 'B' => '-7', 'C' => '23', 'D' => '-23'], 'A',
                'Karena 15 lebih besar dari 8, hasilnya positif: 15 - 8 = 7.', $manual, $published],

            ['Matematika', 'Bilangan Bulat', 'Suhu puncak gunung -3 derajat Celsius, lalu naik 8 derajat. Suhu sekarang adalah ...',
                ['A' => '5 derajat Celsius', 'B' => '-11 derajat Celsius', 'C' => '11 derajat Celsius', 'D' => '-5 derajat Celsius'], 'A',
                'Naik berarti ditambah: -3 + 8 = 5.', $manual, $published],

            ['Matematika', 'Pecahan', 'Bentuk paling sederhana dari pecahan 18/24 adalah ...',
                ['A' => '3/4', 'B' => '2/3', 'C' => '6/8', 'D' => '9/12'], 'A',
                'Pembilang dan penyebut sama-sama dibagi 6.', $manual, $published],

            ['Matematika', 'Aljabar', 'Nilai x yang memenuhi persamaan 3x + 5 = 20 adalah ...',
                ['A' => '5', 'B' => '3', 'C' => '15', 'D' => '25'], 'A',
                'Kurangi 5 di kedua ruas menjadi 3x = 15, lalu bagi 3.', $manual, $published],

            ['Matematika', 'Perbandingan', 'Perbandingan 20 : 35 jika disederhanakan menjadi ...',
                ['A' => '4 : 7', 'B' => '5 : 7', 'C' => '4 : 5', 'D' => '2 : 3'], 'A',
                'Keduanya dibagi 5.', $manual, $draft],

            ['IPA', 'Besaran dan Satuan', 'Satuan besaran pokok untuk kuat arus listrik adalah ...',
                ['A' => 'Ampere', 'B' => 'Volt', 'C' => 'Ohm', 'D' => 'Watt'], 'A',
                'Volt, ohm, dan watt adalah satuan besaran turunan.', $manual, $published],

            ['IPA', 'Zat dan Wujudnya', 'Perubahan wujud dari padat langsung menjadi gas disebut ...',
                ['A' => 'Menyublim', 'B' => 'Menguap', 'C' => 'Mengembun', 'D' => 'Membeku'], 'A',
                'Contohnya kapur barus yang lama-lama habis tanpa mencair.', $manual, $published],

            ['IPA', 'Sistem Pencernaan', 'Enzim yang mengubah amilum menjadi maltosa di dalam mulut adalah ...',
                ['A' => 'Ptialin', 'B' => 'Pepsin', 'C' => 'Tripsin', 'D' => 'Lipase'], 'A',
                'Ptialin terdapat pada air liur. Pepsin bekerja di lambung.', $ai, $review],

            ['IPA', 'Besaran dan Satuan', 'Alat yang tepat untuk mengukur massa suatu benda adalah ...',
                ['A' => 'Neraca', 'B' => 'Termometer', 'C' => 'Dinamometer', 'D' => 'Stopwatch'], 'A',
                'Dinamometer mengukur gaya, bukan massa.', $ai, $review],

            ['Bahasa Indonesia', 'Teks Deskripsi', 'Ciri utama teks deskripsi adalah ...',
                ['A' => 'Menggambarkan objek secara rinci sehingga pembaca seolah melihatnya',
                    'B' => 'Menjelaskan langkah-langkah secara berurutan',
                    'C' => 'Berisi pendapat penulis beserta alasannya',
                    'D' => 'Menceritakan peristiwa berdasarkan urutan waktu'], 'A',
                'Pilihan lain adalah ciri teks prosedur, teks argumentasi, dan teks narasi.', $manual, $published],

            ['Bahasa Indonesia', 'Teks Prosedur', 'Kalimat berikut yang paling tepat digunakan dalam teks prosedur adalah ...',
                ['A' => 'Tuangkan air panas ke dalam gelas.',
                    'B' => 'Air panas itu sangat menyegarkan.',
                    'C' => 'Menurut saya air panas lebih baik.',
                    'D' => 'Pagi itu ibu menuangkan air panas.'], 'A',
                'Teks prosedur memakai kalimat perintah.', $ai, $review],

            ['Bahasa Indonesia', 'Puisi Rakyat', 'Setiap baris pantun umumnya terdiri atas ...',
                ['A' => '8 sampai 12 suku kata', 'B' => '4 sampai 6 suku kata',
                    'C' => '14 sampai 18 suku kata', 'D' => '20 sampai 24 suku kata'], 'A',
                'Pantun terikat aturan jumlah suku kata tiap barisnya.', $ai, $review],
        ];
    }
}
