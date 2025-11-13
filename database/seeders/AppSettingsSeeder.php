<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'app_version',
                'value' => '1.0.0',
                'type' => 'string',
                'description' => 'Current app version'
            ],
            [
                'key' => 'min_order_amount',
                'value' => '10.00',
                'type' => 'number',
                'description' => 'Minimum order amount for checkout'
            ],
            [
                'key' => 'free_shipping_threshold',
                'value' => '50.00',
                'type' => 'number',
                'description' => 'Order amount for free shipping'
            ],
            [
                'key' => 'loyalty_points_per_dollar',
                'value' => '1',
                'type' => 'number',
                'description' => 'Points earned per dollar spent'
            ],
            [
                'key' => 'max_cart_items',
                'value' => '20',
                'type' => 'number',
                'description' => 'Maximum items allowed in cart'
            ],
            [
                'key' => 'review_moderation_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable review moderation'
            ],
            [
                'key' => 'community_moderation_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable community post moderation'
            ]
        ];

        foreach ($settings as $setting) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
