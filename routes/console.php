<?php

use App\Console\Commands\TransitionExams;
use App\Jobs\RecalculateLeaderboard;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Exam statuses are stored, not derived, so something has to move them. Once a
| minute is the resolution the schedule allows and is close enough: an exam
| opening up to a minute late is invisible to a student, who still gets their
| full duration from the moment they start.
|
| withoutOverlapping matters on a slow run -- grading a closed exam with 150
| hanging attempts must not be started again while the first pass is working.
*/
Schedule::command(TransitionExams::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

/*
| Standings are computed on a schedule, never when a ranking page is opened.
| Five minutes is close enough to feel live to a student and far enough apart
| that the heaviest query in the application runs a dozen times an hour rather
| than once per page view.
*/
Schedule::job(new RecalculateLeaderboard)
    ->everyFiveMinutes()
    ->withoutOverlapping();
