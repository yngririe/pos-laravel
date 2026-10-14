<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pos;
use Illuminate\Http\Request;

/** Admin-only: create cashier accounts under the admin's own branch. */
class CashierController extends Controller
{
    public function create()
    {
        return view('cashiers.create', [
            'error'   => session('error', ''),
            'success' => session('success', ''),
        ]);
    }

    public function store(Request $request)
    {
        $full_name = trim((string) $request->input('full_name', ''));
        $username  = trim((string) $request->input('username', ''));
        $email     = trim((string) $request->input('email', ''));
        $password  = (string) $request->input('password', '');
        $confirm   = (string) $request->input('confirm_password', '');

        $error = null;
        if (!$full_name || !$username || !$email || !$password) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($pwError = Pos::passwordError($password, $confirm)) {
            $error = $pwError;
        } elseif (User::where('username', $username)->exists()) {
            $error = 'Username is already taken. Please choose another.';
        }

        if ($error) {
            return back()->withInput($request->only('full_name', 'username', 'email'))->with('error', $error);
        }

        User::create([
            'full_name' => $full_name,
            'username'  => $username,
            'email'     => $email,
            'password'  => $password,
            'status'    => 'active',
            'role'      => 'cashier',
            'branch_id' => Pos::branchId(),
        ]);

        return back()->with('success', 'Cashier account created. Give them the username and password to log in.');
    }
}
