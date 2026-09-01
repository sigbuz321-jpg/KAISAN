<?php

use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;

it('imports questions as drafts written by the importing teacher', function () {
    Subject::factory()->create(['name' => 'Matematika']);
    $guru = User::factory()->guru()->create();

    $import = runQuestionImport(<<<'CSV'
    subject,topic,stem,option_a,option_b,option_c,option_d,answer_key,explanation,difficulty
    Matematika,,Berapa 2 + 2?,4,3,5,6,A,Jumlahkan keduanya.,1100
    CSV, $guru);

    expect($import->successful_rows)->toBe(1);

    $question = Question::first();

    expect($question->status)->toBe(QuestionStatus::Draft)
        ->and($question->source)->toBe(QuestionSource::Manual)
        ->and($question->created_by)->toBe($guru->id)
        ->and($question->answer_key)->toBe('A')
        ->and($question->difficulty)->toBe(1100)
        ->and($question->options)->toBe(['A' => '4', 'B' => '3', 'C' => '5', 'D' => '6']);
});

it('never lets an imported question arrive already published', function () {
    Subject::factory()->create(['name' => 'Matematika']);

    runQuestionImport(<<<'CSV'
    subject,topic,stem,option_a,option_b,option_c,option_d,answer_key,explanation,difficulty
    Matematika,,Berapa 2 + 2?,4,3,5,6,A,,
    CSV);

    expect(Question::query()->status(QuestionStatus::Published)->count())->toBe(0);
});

it('fails a row whose subject does not exist', function () {
    $import = runQuestionImport(<<<'CSV'
    subject,topic,stem,option_a,option_b,option_c,option_d,answer_key,explanation,difficulty
    Fisika Kuantum,,Berapa 2 + 2?,4,3,5,6,A,,
    CSV);

    expect($import->getFailedRowsCount())->toBe(1)
        ->and(Question::count())->toBe(0)
        ->and(Subject::count())->toBe(0);
});

it('fails a row whose chapter belongs to another subject', function () {
    Subject::factory()->create(['name' => 'Matematika']);
    $other = Subject::factory()->create(['name' => 'IPA']);
    Topic::factory()->create(['subject_id' => $other->id, 'name' => 'Fotosintesis']);

    $import = runQuestionImport(<<<'CSV'
    subject,topic,stem,option_a,option_b,option_c,option_d,answer_key,explanation,difficulty
    Matematika,Fotosintesis,Berapa 2 + 2?,4,3,5,6,A,,
    CSV);

    expect($import->getFailedRowsCount())->toBe(1)
        ->and(Question::count())->toBe(0);
});

it('rejects an answer key outside A to D', function () {
    Subject::factory()->create(['name' => 'Matematika']);

    $import = runQuestionImport(<<<'CSV'
    subject,topic,stem,option_a,option_b,option_c,option_d,answer_key,explanation,difficulty
    Matematika,,Berapa 2 + 2?,4,3,5,6,Z,,
    CSV);

    expect($import->getFailedRowsCount())->toBe(1)
        ->and(Question::count())->toBe(0);
});

it('updates rather than duplicating when the same file is uploaded twice', function () {
    Subject::factory()->create(['name' => 'Matematika']);

    $csv = <<<'CSV'
    subject,topic,stem,option_a,option_b,option_c,option_d,answer_key,explanation,difficulty
    Matematika,,Berapa 2 + 2?,4,3,5,6,A,,
    CSV;

    runQuestionImport($csv);
    runQuestionImport($csv);

    expect(Question::count())->toBe(1);
});

it('attaches the question to a chapter of the same subject', function () {
    $subject = Subject::factory()->create(['name' => 'Matematika']);
    Topic::factory()->create(['subject_id' => $subject->id, 'name' => 'Aljabar']);

    runQuestionImport(<<<'CSV'
    subject,topic,stem,option_a,option_b,option_c,option_d,answer_key,explanation,difficulty
    Matematika,Aljabar,Berapa 2x jika x=2?,4,3,5,6,A,,
    CSV);

    expect(Question::first()->topic->name)->toBe('Aljabar');
});
