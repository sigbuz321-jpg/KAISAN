<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.masuk');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // One message for both wrong email and wrong password: telling them
            // apart lets anyone probe which accounts exist.
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        $user = Auth::user();

        if ($user instanceof User && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang dinonaktifkan. Hubungi admin bimbel.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->to($this->destinationFor($user, $request));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }

    /**
     * Where to send someone once they are signed in.
     *
     * A visitor heading somewhere before they met the login screen goes back
     * there -- but only if their role can actually open it. Filament stores an
     * intended URL as soon as a guest touches a panel, so without this check a
     * browser that had once opened /guru threw the next student who signed in
     * on it straight at a panel that refuses them, which reads as a failed
     * login rather than a wrong door.
     */
    private function destinationFor(User $user, Request $request): string
    {
        $home = $this->homeFor($user);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $home;
        }

        $path = '/'.ltrim((string) parse_url($intended, PHP_URL_PATH), '/');

        return $this->mayVisit($user, $path) ? $intended : $home;
    }

    /**
     * Whether this role has any business at that path.
     *
     * Deliberately coarse: it guards the three areas the routes divide into,
     * and leaves the Policies to decide anything finer.
     */
    private function mayVisit(User $user, string $path): bool
    {
        $under = fn (string $prefix) => $path === $prefix || str_starts_with($path, $prefix.'/');

        if ($under('/admin')) {
            return $user->isAdmin();
        }

        if ($under('/guru')) {
            return $user->role->canAccessPanel();
        }

        foreach (['/latihan', '/ujian', '/peringkat'] as $studentArea) {
            if ($under($studentArea)) {
                return $user->isMurid();
            }
        }

        // Everything else -- the home page, changing a password -- is shared.
        return true;
    }

    /**
     * Where signing in lands you.
     *
     * Teachers get their own panel, so sending them to /admin would be a 403
     * on the first screen after a successful login.
     */
    private function homeFor(mixed $user): string
    {
        if (! $user instanceof User || ! $user->role->canAccessPanel()) {
            return '/';
        }

        return $user->isAdmin() ? '/admin' : '/guru';
    }
}
