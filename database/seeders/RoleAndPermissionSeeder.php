<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Spatie Cache Reset
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ১. Permissions তালিকা তৈরি
        $permissions = [
            'manage-dashboard',
            'manage-roles',
            'manage-users',
            'manage-categories',
            'manage-brands',
            'manage-products',
            'manage-orders',
            'order-statuses',
            'manage-courier-services',
            'manage-settings',
            'manage-sliders',
            'manage-payment-gateways',
            'manage-coupons',
            'manage-wishlists',
            'manage-product-reviews',
            'manage-abandoned-carts',
            'manage-sms-email-templates',
            'manage-email-configurations',
            'manage-sms-gateways',
            'manage-custom-messages',
            'manage-pages',
            'manage-contact-messages'

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ২. Roles তৈরি এবং Permission Assign
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        // Super Admin সব পারমিশন পাবে
        $superAdminRole->syncPermissions(Permission::all());

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        // Manager নির্দিষ্ট কিছু পারমিশন পাবে
        $managerRole->syncPermissions([
            'manage-dashboard',
            'manage-products',
            'manage-orders',
        ]);

        // ৩. ডিফল্ট Super Admin User তৈরি করা
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'phone' => '01700000000',
                'password' => Hash::make('12345678'),
                'user_type' => 'admin',
                'status' => true,
            ]
        );

        // ইউজারকে Super Admin রোল অ্যাসাইন করা
        $adminUser->assignRole($superAdminRole);
    }
}
