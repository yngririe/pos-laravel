<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->toArray();

        // The receipt/appearance settings live in a single row (id = 1); create it if missing.
        $receipt = Pos::one('SELECT * FROM receipt_settings WHERE id = 1');
        if (!$receipt) {
            DB::table('receipt_settings')->insert(['id' => 1, 'store_name' => 'Twist & Roll']);
            $receipt = Pos::one('SELECT * FROM receipt_settings WHERE id = 1');
        }

        $success_msg = session('success_msg', '');
        $error_msg   = session('error_msg', '');

        return view('pages.profile', Pos::shared('profile') + compact('user', 'receipt', 'success_msg', 'error_msg'));
    }

    /** One POST endpoint, switched on the hidden "action" field (same as the old profile.php forms). */
    public function update(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->role === 'admin';

        switch ($request->input('action')) {
            case 'edit_profile':
                return $this->editProfile($request, $user);

            case 'change_password':
                return $this->changePassword($request, $user);

            case 'upload_avatar':
                return $this->uploadImage($request, 'avatar', 'assets/images/avatars', 'Avatar updated successfully.', function ($path) use ($user) {
                    $user->update(['avatar' => '/' . $path]);
                });

            case 'upload_logo':
                abort_unless($isAdmin, 403);
                return $this->uploadImage($request, 'logo', 'assets/images', 'Logo updated successfully.', function ($path) {
                    DB::table('receipt_settings')->where('id', 1)->update(['logo_path' => $path]);
                });

            case 'toggle_dark_mode':
                abort_unless($isAdmin, 403);
                $dark = $request->filled('dark_mode') ? 1 : 0;
                DB::table('receipt_settings')->where('id', 1)->update(['dark_mode' => $dark]);
                return response()->json(['success' => true, 'dark_mode' => $dark]);

            case 'save_receipt':
                abort_unless($isAdmin, 403);
                DB::table('receipt_settings')->where('id', 1)->update([
                    'store_address'     => trim((string) $request->input('store_address', '')),
                    'receipt_header'    => trim((string) $request->input('receipt_header', '')),
                    'receipt_footer'    => trim((string) $request->input('receipt_footer', '')),
                    'show_discount'     => $request->has('show_discount') ? 1 : 0,
                    'show_order_type'   => $request->has('show_order_type') ? 1 : 0,
                    'show_payment_type' => $request->has('show_payment_type') ? 1 : 0,
                    'show_beeper'       => $request->has('show_beeper') ? 1 : 0,
                ]);
                return $this->done('Receipt settings saved successfully.');
        }

        return redirect()->route('profile');
    }

    private function editProfile(Request $request, User $user): RedirectResponse
    {
        $username = trim((string) $request->input('username', ''));
        $email    = trim((string) $request->input('email', ''));

        if ($username === '') {
            return $this->done(null, 'Username is required.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->done(null, 'Please enter a valid email address.');
        }
        if (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
            return $this->done(null, 'Username is already taken. Please choose another.');
        }

        $user->update([
            'full_name' => trim((string) $request->input('full_name', '')),
            'username'  => $username,
            'email'     => $email,
            'phone'     => trim((string) $request->input('phone', '')),
        ]);

        return $this->done('Profile updated successfully.');
    }

    private function changePassword(Request $request, User $user): RedirectResponse
    {
        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('confirm_password', '');

        if (!Hash::check($current, $user->password)) {
            return $this->done(null, 'Current password is incorrect.');
        }
        if ($error = Pos::passwordError($new, $confirm)) {
            return $this->done(null, str_replace('Passwords do not match.', 'New passwords do not match.', $error));
        }

        $user->update(['password' => $new]);   // hashed by the model cast

        return $this->done('Password updated successfully.');
    }

    /** Validated image upload into public/<dir>. Only real images, max 2 MB. */
    private function uploadImage(Request $request, string $field, string $dir, string $okMessage, callable $store): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            $field => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        if ($validator->fails()) {
            return $this->done(null, $validator->errors()->first($field));
        }

        $file     = $request->file($field);
        $filename = $field . '_' . time() . '.' . $file->extension();
        $file->move(public_path($dir), $filename);

        $store($dir . '/' . $filename);

        return $this->done($okMessage);
    }

    private function done(?string $success, ?string $error = null): RedirectResponse
    {
        return redirect()->route('profile')->with([
            'success_msg' => $success ?? '',
            'error_msg'   => $error ?? '',
        ]);
    }
}
