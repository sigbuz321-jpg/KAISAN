<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Where signing out of a panel lands you.
 *
 * Filament's own response returns to that panel's login screen, which leaves
 * a teacher staring at the teacher login and no obvious way across to the
 * student side. Everyone comes back to the front page instead, where all
 * three doors are listed.
 */
class FilamentLogoutResponse implements LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('beranda');
    }
}
