<?php

use App\Filament\Resources\Seasons\Pages\ListSeasons;
use App\Filament\Resources\Seasons\SeasonResource;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\LeaderboardEntry;
use App\Models\Season;
use App\Models\Subject;
use App\Models\User;
use App\Services\Leaderboard\LeaderboardCalculator;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();

    $this->season = Season::factory()->active()->create(['name' => 'Semester Ganjil']);
    $this->mapel = Subject::factory()->create(['name' => 'Matematika']);
    $this->murid = User::factory()->murid()->create(['name' => 'Murid Uji']);
});

function skorMusim(User $student, string $score, ?Subject $subject = null): void
{
    ExamAttempt::factory()->submitted()->create([
        'exam_id' => Exam::factory()->graded()->create([
            'season_id' => test()->season->id,
            'subject_id' => ($subject ?? test()->mapel)->id,
        ])->id,
        'user_id' => $student->id,
        'score' => $score,
    ]);
}

it('shows the combined standings', function () {
    $juara = User::factory()->murid()->create(['name' => 'Anak Juara']);
    skorMusim($juara, '95.00');
    skorMusim($this->murid, '60.00');

    app(LeaderboardCalculator::class)->recalculate($this->season);

    $this->actingAs($this->murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Anak Juara')
        ->assertSee('Semester Ganjil');
});

it('shows a per subject board when one is chosen', function () {
    $ipa = Subject::factory()->create(['name' => 'IPA']);
    $ahliIpa = User::factory()->murid()->create(['name' => 'Jago IPA']);

    skorMusim($this->murid, '90.00');
    skorMusim($ahliIpa, '90.00', $ipa);

    app(LeaderboardCalculator::class)->recalculate($this->season);

    $this->actingAs($this->murid)
        ->get(route('peringkat.index', ['mapel' => $ipa->id]))
        ->assertOk()
        ->assertSee('Jago IPA');
});

it('falls back to the combined board for an unknown subject', function () {
    skorMusim($this->murid, '70.00');
    app(LeaderboardCalculator::class)->recalculate($this->season);

    $this->actingAs($this->murid)
        ->get(route('peringkat.index', ['mapel' => 999999]))
        ->assertOk()
        ->assertSee('Murid Uji');
});

it('marks the viewing student on the board', function () {
    skorMusim($this->murid, '70.00');
    app(LeaderboardCalculator::class)->recalculate($this->season);

    $this->actingAs($this->murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('(kamu)');
});

it('shows a student their own place even when far outside the top', function () {
    // The top twenty are shown, but a student in 21st still needs to see
    // themselves -- otherwise the page is useless to most of the school.
    foreach (range(1, 25) as $i) {
        skorMusim(User::factory()->murid()->create(), (string) (100 - $i).'.00');
    }
    skorMusim($this->murid, '1.00');

    app(LeaderboardCalculator::class)->recalculate($this->season);

    $this->actingAs($this->murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Posisimu')
        ->assertSee('Murid Uji');
});

it('says so plainly when nobody has a mark yet', function () {
    $this->actingAs($this->murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Belum ada nilai ujian di musim ini');
});

it('says so plainly when no season is running', function () {
    Season::query()->update(['is_active' => false]);

    $this->actingAs($this->murid)
        ->get(route('peringkat.index'))
        ->assertOk()
        ->assertSee('Belum ada musim yang berjalan');
});

it('keeps staff off the student ranking page', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get(route('peringkat.index'))
        ->assertForbidden();
});

it('sends a guest to the sign-in page', function () {
    $this->get(route('peringkat.index'))->assertRedirect(route('masuk'));
});

it('lets the admin open the seasons page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(SeasonResource::getUrl('index'))
        ->assertOk();
});

it('keeps students out of the seasons page', function () {
    $this->actingAs($this->murid)
        ->get(SeasonResource::getUrl('index'))
        ->assertForbidden();
});

it('lets the admin start a new season from the panel', function () {
    // The exit criterion for M6: done from the panel, never a terminal.
    skorMusim($this->murid, '80.00');
    app(LeaderboardCalculator::class)->recalculate($this->season);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListSeasons::class)
        ->callAction('resetMusim', data: ['nama' => 'Semester Genap', 'paham' => true]);

    expect(Season::current()->name)->toBe('Semester Genap')
        // Cleared board, intact history.
        ->and(LeaderboardEntry::where('season_id', Season::current()->id)->count())->toBe(0)
        ->and(ExamAttempt::count())->toBe(1);
});

it('refuses to reset without the confirmation ticked', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListSeasons::class)
        ->callAction('resetMusim', data: ['nama' => 'Semester Genap', 'paham' => false])
        ->assertHasActionErrors(['paham']);

    expect(Season::current()->name)->toBe('Semester Ganjil');
});

it('does not offer the reset to a teacher', function () {
    Livewire::actingAs(User::factory()->guru()->create())
        ->test(ListSeasons::class)
        ->assertActionHidden('resetMusim');
});
