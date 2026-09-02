<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Who may open the Horizon dashboard.
     *
     * The dashboard exposes job payloads and failure traces, so it is the
     * owner's tool only -- docs/05-DEPLOYMENT.md lists it as admin-restricted.
     * Teachers get what they need from the "Permintaan Soal AI" page instead.
     *
     * The stock scaffolding shipped an empty email list, which locks out
     * everyone in production. Keyed on the role instead, so it keeps working
     * when the client changes their own email address.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user) => $user?->isAdmin() ?? false);
    }

    /**
     * Horizon's own scaffolding waves through every request when the app is in
     * the local environment. The dev VPS runs as `local` and shares a machine
     * with unrelated services, so that shortcut is removed here: the gate is
     * the only way in, in every environment.
     *
     * security.md: when in doubt, take the stricter option.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(fn ($request) => Gate::check('viewHorizon', [$request->user()]));
    }
}
