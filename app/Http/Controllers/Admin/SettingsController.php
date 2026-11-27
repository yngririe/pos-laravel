<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Support\Pos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $receipt = Pos::one('SELECT * FROM receipt_settings WHERE id = 1');
        if (!$receipt) {
            DB::table('receipt_settings')->insert(['id' => 1, 'store_name' => 'Twist & Roll']);
            $receipt = Pos::one('SELECT * FROM receipt_settings WHERE id = 1');
        }

        $menuItems = MenuItem::orderBy('category')->orderBy('name')->get();

        return view('pages.settings', Pos::shared('settings') + [
            'receipt' => $receipt,
            'menuItems' => $menuItems,
            'success_msg' => session('success_msg', ''),
            'error_msg' => session('error_msg', ''),
        ]);
    }

    public function saveReceipt(Request $request)
    {
        DB::table('receipt_settings')->updateOrInsert(['id' => 1], [
            'store_name'        => trim((string) $request->input('store_name', 'Twist & Roll')) ?: 'Twist & Roll',
            'store_address'     => trim((string) $request->input('store_address', '')),
            'store_contact'     => trim((string) $request->input('store_contact', '')),
            'fb_page_url'       => trim((string) $request->input('fb_page_url', '')),
            'store_tin'         => trim((string) $request->input('store_tin', '')),
            'receipt_header'    => trim((string) $request->input('receipt_header', '')),
            'receipt_footer'    => trim((string) $request->input('receipt_footer', '')),
            'show_discount'     => $request->boolean('show_discount'),
            'show_cashier'      => $request->boolean('show_cashier'),
            'show_order_type'   => $request->boolean('show_order_type'),
            'show_payment_type' => $request->boolean('show_payment_type'),
            'show_beeper'       => $request->boolean('show_beeper'),
        ]);

        return back()->with('success_msg', 'Receipt settings saved successfully.')->withFragment('receipt-settings');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $file = $request->file('logo');
        $dir = public_path('assets/images/settings');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'store_logo_' . time() . '.' . $file->extension();
        $file->move($dir, $filename);
        DB::table('receipt_settings')->updateOrInsert(['id' => 1], ['logo_path' => 'assets/images/settings/' . $filename]);
        return back()->with('success_msg', 'Store logo updated.')->withFragment('receipt-settings');
    }

    public function storeMenu(Request $request)
    {
        $data = $this->validateMenu($request);
        if ($request->hasFile('image')) $data['image'] = $this->storeMenuImage($request);
        $data['is_active'] = $request->boolean('is_active');
        MenuItem::create($data);
        return back()->with('success_msg', 'Menu item added successfully.')->withFragment('menu-settings');
    }

    public function updateMenu(Request $request, MenuItem $menuItem)
    {
        $data = $this->validateMenu($request, $menuItem->id);
        if ($request->hasFile('image')) {
            $this->deleteManagedMenuImage($menuItem->image);
            $data['image'] = $this->storeMenuImage($request);
        }
        $data['is_active'] = $request->boolean('is_active');
        $menuItem->update($data);
        return back()->with('success_msg', 'Menu item updated successfully.')->withFragment('menu-settings');
    }

    public function destroyMenu(MenuItem $menuItem)
    {
        $this->deleteManagedMenuImage($menuItem->image);
        $menuItem->delete();
        return back()->with('success_msg', 'Menu item deleted.')->withFragment('menu-settings');
    }

    private function validateMenu(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('menu_items', 'name')->ignore($ignoreId)],
            'category' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'discounted_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
    }

    private function storeMenuImage(Request $request): string
    {
        $file = $request->file('image');
        $dir = public_path('assets/images/menu');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'menu_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $file->extension();
        $file->move($dir, $filename);
        return 'assets/images/menu/' . $filename;
    }

    private function deleteManagedMenuImage(?string $path): void
    {
        if (!$path || !str_starts_with($path, 'assets/images/menu/')) return;
        $full = public_path($path);
        if (is_file($full)) @unlink($full);
    }
}
