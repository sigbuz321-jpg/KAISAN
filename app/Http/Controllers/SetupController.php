<?php

namespace App\Http\Controllers;

use App\Actions\CreateFirstAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SetupController extends Controller
{
    public function show(): View
    {
        abort_unless(CreateFirstAdmin::isAvailable(), Response::HTTP_NOT_FOUND);

        return view('setup');
    }

    public function store(Request $request, CreateFirstAdmin $createFirstAdmin): RedirectResponse
    {
        abort_unless(CreateFirstAdmin::isAvailable(), Response::HTTP_NOT_FOUND);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Auth::login($createFirstAdmin->handle($data));

        return redirect('/admin');
    }
}
