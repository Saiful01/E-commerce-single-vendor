<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'phone' => '01700000000',
            'password' => Hash::make('12345678'),
            'user_type' => 'admin',
            'status' => true,
        ]);
        $this->call([ RoleAndPermissionSeeder::class, ]);
        $this->call([ OrderStatusSeeder::class, ]);
        $this->call([ CatalogDemoSeeder::class, ]);
        $this->call([ EcommerceExtrasSeeder::class, ]);
        $this->call([ ShopDemoSeeder::class, ]);
        $this->call(ProductImageSeeder::class);
    }
}
