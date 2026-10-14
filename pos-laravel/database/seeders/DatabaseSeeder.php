<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\ReceiptSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Menu (same items/prices as the original dump) ───────────────────
        $menu = [
            ['Eruption',            229, 184, 'eruption.png'],
            ['Cheesy Shrimp Bomb',  174, 140, 'cheesyshrimp.png'],
            ['Crazy Crab',          164, 132, 'crazycrab.png'],
            ['Tori Floss Maki',     154, 124, 'torifloss.png'],
            ['Ebi Tempura Roll',    154, 124, 'ebitemp.png'],
            ['Mango Craze',         144, 116, 'mangocraze.png'],
            ['Garden Maki',         179, 144, 'gardenmaki.png'],
            ['Red Hot Chili Roll',  189, 152, 'redhotchili.png'],
            ['California Maki',     144, 116, 'calimaki.png'],
        ];

        foreach ($menu as [$name, $price, $discounted, $image]) {
            MenuItem::updateOrCreate(['name' => $name], [
                'price'            => $price,
                'discounted_price' => $discounted,
                'category'         => 'sushi',
                'image'            => 'assets/images/' . $image,
            ]);
        }

        // ── Receipt / appearance settings (single shared row, id = 1) ───────
        if (!ReceiptSetting::find(1)) {
            $settings = new ReceiptSetting(['store_name' => 'Twist & Roll']);
            $settings->id = 1;
            $settings->save();
        }

        // ── First admin account ─────────────────────────────────────────────
        if (!User::where('username', 'admin')->exists()) {
            $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(14);

            $admin = User::create([
                'full_name'   => 'Admin',
                'branch_name' => 'Main Branch',
                'username'    => 'admin',
                'email'       => env('SEED_ADMIN_EMAIL', ''),
                'password'    => $password,
                'role'        => 'admin',
                'status'      => 'active',
            ]);
            $admin->update(['branch_id' => $admin->id]);

            $this->command?->warn("Admin login created ->  username: admin   password: {$password}");
            $this->command?->warn('Change it from the Profile page after your first login.');
        }
    }
}
