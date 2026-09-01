<?php

use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function rawQuestion(array $overrides = []): array
{
    return array_merge([
        'subject_id' => Subject::factory()->create()->id,
        'stem' => 'Ibu kota Indonesia?',
        'options' => json_encode(['A' => 'Jakarta', 'B' => 'Bandung', 'C' => 'Medan', 'D' => 'Surabaya']),
        'answer_key' => 'A',
        'source' => 'manual',
        'status' => 'draft',
        'created_by' => User::factory()->guru()->create()->id,
        'stem_hash' => Question::hashStem('Ibu kota Indonesia?'),
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides);
}

it('insists on exactly four options', function () {
    expect(fn () => DB::table('questions')->insert(rawQuestion([
        'options' => json_encode(['A' => 'Ya', 'B' => 'Tidak']),
    ])))->toThrow(QueryException::class);
});

it('insists the answer key names an option that exists', function () {
    expect(fn () => DB::table('questions')->insert(rawQuestion([
        'answer_key' => 'D',
        'options' => json_encode(['A' => '1', 'B' => '2', 'C' => '3', 'E' => '4']),
    ])))->toThrow(QueryException::class);
});

it('rejects an answer key outside A to D', function () {
    expect(fn () => DB::table('questions')->insert(rawQuestion(['answer_key' => 'Z'])))
        ->toThrow(QueryException::class);
});

it('refuses two questions with the same wording in one subject', function () {
    $subject = Subject::factory()->create();
    Question::factory()->create(['subject_id' => $subject->id, 'stem' => 'Ibu kota Indonesia?']);

    expect(fn () => Question::factory()->create([
        'subject_id' => $subject->id,
        'stem' => '  ibu   KOTA indonesia?  ',
    ]))->toThrow(QueryException::class);
});

it('allows the same wording under a different subject', function () {
    Question::factory()->create(['stem' => 'Ibu kota Indonesia?']);
    Question::factory()->create(['stem' => 'Ibu kota Indonesia?']);

    expect(Question::count())->toBe(2);
});

it('returns options in A to D order however they were stored', function () {
    $question = Question::factory()->create([
        'options' => ['C' => 'tiga', 'A' => 'satu', 'D' => 'empat', 'B' => 'dua'],
        'answer_key' => 'A',
    ]);

    expect(array_keys($question->orderedOptions()))->toBe(['A', 'B', 'C', 'D']);
});
