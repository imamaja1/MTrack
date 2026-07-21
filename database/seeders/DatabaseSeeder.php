<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@mtrack.test',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // 2. Create Admin (Store Owner)
        $admin = User::create([
            'name' => 'Store Admin Budi',
            'email' => 'admin@mtrack.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 3. Create Operator (Cashier)
        User::create([
            'name' => 'Operator Siti',
            'email' => 'operator@mtrack.test',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'admin_id' => $admin->id,
            'is_active' => true,
        ]);

        // 4. Dummy Products for the Admin
        $products = [
            ['name' => 'Beras Pandan Wangi 5Kg', 'cost' => 60000, 'sell' => 68000, 'stock' => 50],
            ['name' => 'Minyak Goreng Bimoli 2L', 'cost' => 32000, 'sell' => 36000, 'stock' => 40],
            ['name' => 'Gula Pasir Gulaku 1Kg', 'cost' => 14000, 'sell' => 17000, 'stock' => 100],
            ['name' => 'Telur Ayam 1Kg', 'cost' => 24000, 'sell' => 28000, 'stock' => 30],
            ['name' => 'Indomie Goreng (Karton)', 'cost' => 100000, 'sell' => 115000, 'stock' => 20],
            ['name' => 'Tepung Terigu Segitiga Biru 1Kg', 'cost' => 10000, 'sell' => 13000, 'stock' => 5], // Low stock to test alert
            ['name' => 'Kopi Kapal Api 380gr', 'cost' => 25000, 'sell' => 30000, 'stock' => 25],
            ['name' => 'Teh Botol Sosro 450ml', 'cost' => 3000, 'sell' => 5000, 'stock' => 80],
            ['name' => 'Sabun Mandi Lifebuoy', 'cost' => 3500, 'sell' => 5000, 'stock' => 60],
            ['name' => 'Pasta Gigi Pepsodent 190g', 'cost' => 12000, 'sell' => 15000, 'stock' => 45],
        ];

        foreach ($products as $index => $item) {
            Product::create([
                'admin_id' => $admin->id,
                'sku' => 'SKU-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'stock' => $item['stock'],
                'min_stock_alert' => 10,
            ]);
        }
    }
}
