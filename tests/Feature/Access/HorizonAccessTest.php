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

it('refuses an anonymous visitor even in the local environment', function () {
    // Horizon's own scaffolding waves everyone through when the app is local.
    // The dev VPS runs as local and shares a machine with other services.
    $this->app['env'] = 'local';

    $this->get('/horizon')->assertForbidden();
});

it('refuses a teacher on the dashboard route itself', function () {
    $this->actingAs(User::factory()->guru()->create())
        ->get('/horizon')
        ->assertForbidden();
});

it('admits the admin to the dashboard route', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/horizon')
        ->assertOk();
});
