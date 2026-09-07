<?php

namespace App\Providers\Filament;

use App\Filament\Guru\Widgets\GuruOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The teacher's panel.
 *
 * It shares its Resource classes with the admin panel on purpose: the same
 * exam is the same exam, and duplicating the classes would mean two places to
 * fix every bug. What differs is the door, the dashboard, and the navigation
 * order -- a teacher opens Bank Soal many times a day and Musim almost never.
 *
 * Who sees what is still decided by the Policies, not by this file.
 */
class GuruPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('guru')
            ->path('guru')
            ->login()
            ->brandName('KAISAN · Guru')
            ->colors([
                // Same amber as the student pages. See config/design.php.
                'primary' => Color::hex(config('design.colors.primary')),
            ])
            ->viteTheme('resources/css/filament/panel.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->pages([
                Dashboard::class,
            ])
            // Most-used group first. Nothing hides behind a dropdown, so a
            // teacher never has to remember where something lives.
            ->navigationGroups([
                'Akademik',
                'Referensi',
            ])
            // Question generation runs on a queue, so the teacher who asked is
            // told through the panel's bell when the batch is ready.
            ->databaseNotifications()
            ->discoverWidgets(in: app_path('Filament/Guru/Widgets'), for: 'App\Filament\Guru\Widgets')
            ->widgets([
                GuruOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
