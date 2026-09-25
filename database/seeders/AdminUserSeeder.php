<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the store administrator from the configured credentials.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrNew(['email' => config('app.admin.email')]);

        $admin->forceFill([
            'name' => config('app.admin.name'),
            'password' => config('app.admin.password'),
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'is_admin' => true,
        ])->save();
    }
}
