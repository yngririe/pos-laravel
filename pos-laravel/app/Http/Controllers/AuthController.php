<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $username = trim((string) $request->input('username', ''));
        $password = trim((string) $request->input('password', ''));

        if ($username !== '' && Auth::attempt(['username' => $username, 'password' => $password])) {
            $request->session()->regenerate();
            return redirect()->route('home');
        }

        return back()->with('login_error', 'Invalid username or password.');
    }

    // GET on purpose: the existing navbar buttons log out with window.location.href.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
