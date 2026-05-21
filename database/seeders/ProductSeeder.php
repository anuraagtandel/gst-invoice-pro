<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'AQUAFINA',
                'volume' => '1000ML',
                'pack_size' => 24,
                'mrp' => 22,
                'trade_price' => 22 / 1.12, // approx base for testing
                'base_price' => (22 / 1.12) / 1.12,
                'gst_rate' => 12,
                'hsn_code' => '22011010',
            ],
            [
                'name' => 'PEPSI',
                'volume' => '600ML',
                'pack_size' => 24,
                'mrp' => 40,
                'trade_price' => 40 / 1.12,
                'base_price' => (40 / 1.12) / 1.12,
                'gst_rate' => 12,
                'hsn_code' => '22021020',
            ],
            [
                'name' => 'MIRINDA',
                'volume' => '600ML',
                'pack_size' => 24,
                'mrp' => 40,
                'trade_price' => 40 / 1.12,
                'base_price' => (40 / 1.12) / 1.12,
                'gst_rate' => 12,
                'hsn_code' => '22021020',
            ],
            [
                'name' => '7UP',
                'volume' => '600ML',
                'pack_size' => 24,
                'mrp' => 40,
                'trade_price' => 40 / 1.12,
                'base_price' => (40 / 1.12) / 1.12,
                'gst_rate' => 12,
                'hsn_code' => '22021020',
            ],
            [
                'name' => 'STING',
                'volume' => '250ML',
                'pack_size' => 24,
                'mrp' => 20,
                'trade_price' => 20 / 1.12,
                'base_price' => (20 / 1.12) / 1.12,
                'gst_rate' => 12,
                'hsn_code' => '22021020',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
