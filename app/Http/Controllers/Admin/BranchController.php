<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pos;
use Illuminate\Http\Request;

/**
 * Create a new branch together with its owner (admin) account.
 * Protected by BRANCH_SETUP_KEY in .env (the page is disabled when it is empty).
 */
class BranchController extends Controller
{
    public function create()
    {
        return view('branches.create', [
            'error'    => session('error', ''),
            'success'  => session('success', ''),
            'disabled' => !config('pos.branch_setup_key'),
        ]);
    }

    public function store(Request $request)
    {
        $setupKey = (string) config('pos.branch_setup_key');

        $branch_name = trim((string) $request->input('branch_name', ''));
        $full_name   = trim((string) $request->input('full_name', ''));
        $username    = trim((string) $request->input('username', ''));
        $email       = trim((string) $request->input('email', ''));
        $password    = (string) $request->input('password', '');
        $confirm     = (string) $request->input('confirm_password', '');

        $error = null;
        if ($setupKey === '' || !hash_equals($setupKey, (string) $request->input('setup_key', ''))) {
            $error = 'Incorrect setup key.';
        } elseif (!$branch_name || !$full_name || !$username || !$email || !$password) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($pwError = Pos::passwordError($password, $confirm)) {
            $error = $pwError;
        } elseif (User::where('username', $username)->exists()) {
            $error = 'Username is already taken. Please choose another.';
        }

        if ($error) {
            return back()
                ->withInput($request->only('branch_name', 'full_name', 'username', 'email'))
                ->with('error', $error);
        }

        $admin = User::create([
            'branch_name' => $branch_name,
            'full_name'   => $full_name,
            'username'    => $username,
            'email'       => $email,
            'password'    => $password,
            'status'      => 'active',
            'role'        => 'admin',
        ]);
        $admin->update(['branch_id' => $admin->id]);   // a new admin owns their own branch

        return back()->with('success', 'Branch admin account created! They can now log in.');
    }
}
