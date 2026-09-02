<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

use App\Filament\Imports\QuestionImporter;
use App\Filament\Imports\UserImporter;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User as ImportActor;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\Import;

function runImport(string $csv, array $options = []): Import
{
    $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
    file_put_contents($path, $csv);

    $import = Import::create([
        'user_id' => ImportActor::factory()->admin()->create()->id,
        'file_name' => 'murid.csv',
        'file_path' => $path,
        'importer' => UserImporter::class,
        'total_rows' => substr_count(trim($csv), "\n"),
    ]);

    $rows = array_map(
        fn (array $r) => array_combine(['name', 'email', 'classroom'], $r),
        array_map('str_getcsv', array_slice(explode("\n", trim($csv)), 1))
    );

    (new ImportCsv(
        $import,
        rows: $rows,
        columnMap: ['name' => 'name', 'email' => 'email', 'classroom' => 'classroom'],
        options: array_merge(['default_password' => 'rahasia123'], $options),
    ))->handle();

    return $import->refresh();
}

function runQuestionImport(string $csv, ?ImportActor $actor = null): Import
{
    $columns = ['subject', 'topic', 'stem', 'option_a', 'option_b', 'option_c', 'option_d', 'answer_key', 'explanation', 'difficulty'];

    $import = Import::create([
        'user_id' => ($actor ?? ImportActor::factory()->guru()->create())->id,
        'file_name' => 'soal.csv',
        'file_path' => 'soal.csv',
        'importer' => QuestionImporter::class,
        'total_rows' => substr_count(trim($csv), "\n"),
    ]);

    $rows = array_map(
        fn (array $r) => array_combine($columns, array_pad($r, count($columns), null)),
        array_map('str_getcsv', array_slice(explode("\n", trim($csv)), 1))
    );

    (new ImportCsv(
        $import,
        rows: $rows,
        columnMap: array_combine($columns, $columns),
        options: [],
    ))->handle();

    return $import->refresh();
}

/**
 * An OpenAI-compatible router reply. Pass an array to have it encoded as the
 * JSON the parser expects, or a string to simulate a malformed answer.
 *
 * @param  list<array<string, mixed>>|string  $content
 * @return array<string, mixed>
 */
function aiRouterPayload(
    array|string $content,
    int $promptTokens = 400,
    int $completionTokens = 900,
    string $model = 'test-model',
): array {
    return [
        'model' => $model,
        'choices' => [['message' => ['content' => is_string($content) ? $content : json_encode($content)]]],
        'usage' => ['prompt_tokens' => $promptTokens, 'completion_tokens' => $completionTokens],
    ];
}

/**
 * One well-formed candidate question, so a test only states the part it cares about.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function aiQuestion(string $stem = 'Berapakah hasil dari 2 tambah 2?', array $overrides = []): array
{
    return array_merge([
        'stem' => $stem,
        'options' => ['A' => 'empat', 'B' => 'tiga', 'C' => 'lima', 'D' => 'enam'],
        'answer_key' => 'A',
        'explanation' => 'Dua ditambah dua sama dengan empat.',
    ], $overrides);
}

/**
 * An exam with real published questions attached in order.
 *
 * @param  array<string, mixed>  $attributes  passed to ExamFactory
 * @param  'draft'|'scheduled'|'active'|'closed'  $state
 */
function examWithQuestions(int $questions = 4, array $attributes = [], string $state = 'active'): Exam
{
    $exam = Exam::factory()->{$state}()->create($attributes);

    Question::factory()
        ->published()
        ->count($questions)
        ->create(['subject_id' => $exam->subject_id])
        ->each(fn (Question $q, int $i) => $exam->questions()->attach($q, ['order' => $i]));

    return $exam->refresh();
}

/** The answer keys of an exam, in order, as question id => key. */
function answerKeysOf(Exam $exam): array
{
    return $exam->questions()->pluck('answer_key', 'questions.id')->all();
}
