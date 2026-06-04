<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials + ['active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->default_project_id) {
                session(['current_project_id' => $user->default_project_id]);
            }
            $default = $user->getDefaultUrl();
            return redirect()->intended($default ?? route('projects.index'));
        }

        return back()->withErrors([
            'username' => __('Invalid credentials.'),
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
