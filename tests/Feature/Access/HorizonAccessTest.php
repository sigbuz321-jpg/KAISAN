<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

it('lets the admin open the queue dashboard', function () {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeTrue();
});

it('keeps teachers out of the queue dashboard', function () {
    // The dashboard shows job payloads and failure traces for the whole
    // school. Teachers get their own requests from the AI request page.
    $guru = User::factory()->guru()->create();

    expect(Gate::forUser($guru)->allows('viewHorizon'))->toBeFalse();
});

it('keeps students out of the queue dashboard', function () {
    $murid = User::factory()->murid()->create();

    expect(Gate::forUser($murid)->allows('viewHorizon'))->toBeFalse();
});

it('keeps anonymous visitors out of the queue dashboard', function () {
    expect(Gate::allows('viewHorizon'))->toBeFalse();
});

it('registers the dashboard route', function () {
    expect(Route::has('horizon.index'))->toBeTrue();
});
