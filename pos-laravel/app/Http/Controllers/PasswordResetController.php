<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/** Email reset links using the existing password_resets table (tokens stored as SHA-256). */
class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot', ['alert' => session('alert', [])]);
    }

    public function sendLink(Request $request)
    {
        $email = trim((string) $request->input('email', ''));

        if ($email === '') {
            return $this->forgotAlert('error', 'Something went wrong. Please try again.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->forgotAlert('error', 'No account found with that email.');
        }

        $token = bin2hex(random_bytes(32));

        DB::table('password_resets')->where('email', $email)->delete();
        DB::table('password_resets')->insert([
            'email'      => $email,
            'token'      => hash('sha256', $token),
            'expires_at' => Carbon::now('UTC')->addHour()->format('Y-m-d H:i:s'),
        ]);

        $resetLink = route('password.reset', ['token' => $token, 'email' => $email]);

        try {
            $html = view('emails.reset-password', [
                'fullName'  => $user->full_name,
                'resetLink' => $resetLink,
                'email'     => $email,
            ])->render();

            Mail::html($html, function ($message) use ($email, $user) {
                $message->to($email, $user->full_name)->subject('Reset Your Password - Twist & Roll');
            });
        } catch (\Throwable $e) {
            report($e);
            return $this->forgotAlert('error', 'Something went wrong. Please try again.');
        }

        return $this->forgotAlert('success', 'Reset link sent! Please check your email.');
    }

    public function showReset(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $email = trim((string) $request->query('email', ''));
        $alert = [];
        $valid_token = false;

        if ($token === '' || $email === '') {
            $alert = ['type' => 'error', 'message' => 'Invalid or missing reset link.'];
        } else {
            $reset = $this->findReset($token, $email);
            if (!$reset) {
                $alert = ['type' => 'error', 'message' => 'Invalid reset link.'];
            } elseif ($this->expired($reset)) {
                $alert = ['type' => 'error', 'message' => 'Reset link has expired. Please request a new one.'];
            } else {
                $valid_token = true;
            }
        }

        switch ($request->query('status')) {
            case 'success':
                $alert = ['type' => 'success', 'message' => 'Password updated successfully. You can now login.'];
                $valid_token = false;
                break;
            case 'error':
                $alert = ['type' => 'error', 'message' => 'Something went wrong. Please try again.'];
                break;
            case 'invalid':
                $alert = ['type' => 'error', 'message' => 'Invalid or expired reset link.'];
                break;
        }

        return view('auth.reset', compact('alert', 'valid_token', 'token', 'email'));
    }

    public function reset(Request $request)
    {
        $token    = trim((string) $request->input('token', ''));
        $email    = trim((string) $request->input('email', ''));
        $password = trim((string) $request->input('new_password', ''));
        $confirm  = trim((string) $request->input('confirm_password', ''));

        $back = fn (string $status) => redirect()->route('password.reset', [
            'token' => $token, 'email' => $email, 'status' => $status,
        ]);

        if ($token === '' || $email === '' || $password === '' || $confirm === '') {
            return $back('error');
        }

        $reset = $this->findReset($token, $email);
        if (!$reset || $this->expired($reset)) {
            return $back('invalid');
        }

        if (Pos::passwordError($password, $confirm)) {
            return $back('error');
        }

        // Builder update() bypasses the model cast, so hash explicitly.
        DB::table('users')->where('email', $email)->update(['password' => Hash::make($password)]);
        DB::table('password_resets')->where('email', $email)->delete();

        return redirect()->route('password.reset', ['status' => 'success']);
    }

    private function findReset(string $token, string $email): ?object
    {
        return DB::table('password_resets')
            ->where('email', $email)
            ->where('token', hash('sha256', $token))
            ->first();
    }

    private function expired(object $reset): bool
    {
        return Carbon::parse($reset->expires_at, 'UTC')->isPast();
    }

    private function forgotAlert(string $type, string $message)
    {
        return redirect()->route('password.request')->with('alert', ['type' => $type, 'message' => $message]);
    }
}
