<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The POS stores every timestamp in UTC and buckets "business days" with
        // CONVERT_TZ(..,'+00:00','+08:00'), so the DB session must run in UTC.
        // Strict mode is also relaxed: the report queries SELECT o.* ... GROUP BY o.id,
        // which MariaDB (XAMPP) rejects under ONLY_FULL_GROUP_BY.
        foreach (['mysql', 'mariadb'] as $connection) {
            config([
                "database.connections.$connection.timezone" => '+00:00',
                "database.connections.$connection.strict"   => false,
            ]);
        }
    }

    public function boot(): void
    {
        //
    }
}
