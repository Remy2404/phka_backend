<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user (avoid duplicates)
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@phka.com'],
            [
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+1234567890',
                'is_active' => true,
                'loyalty_points' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create sample categories (avoid duplicates)
        $categories = [
            ['name' => 'Skincare', 'slug' => 'skincare', 'description' => 'Products for healthy skin', 'is_active' => true],
            ['name' => 'Makeup', 'slug' => 'makeup', 'description' => 'Cosmetics and beauty products', 'is_active' => true],
            ['name' => 'Hair Care', 'slug' => 'hair-care', 'description' => 'Shampoos, conditioners and hair treatments', 'is_active' => true],
            ['name' => 'Fragrance', 'slug' => 'fragrance', 'description' => 'Perfumes and colognes', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create sample products (avoid duplicates)
        $products = [
            [
                'name' => 'Hydrating Facial Cleanser',
                'slug' => 'hydrating-facial-cleanser',
                'sku' => 'HFC001',
                'description' => 'Gentle cleanser for all skin types',
                'short_description' => 'Deep cleansing with hydration',
                'price' => 25.99,
                'stock_quantity' => 100,
                'stock_status' => 'in_stock',
                'category_id' => 1,
                'brand' => 'Phka Beauty',
                'is_active' => true,
                'rating' => 4.5,
                'review_count' => 25,
            ],
            [
                'name' => 'Matte Lipstick - Ruby Red',
                'slug' => 'matte-lipstick-ruby-red',
                'sku' => 'MLRR001',
                'description' => 'Long-lasting matte lipstick',
                'short_description' => 'Bold color that lasts all day',
                'price' => 18.50,
                'stock_quantity' => 75,
                'stock_status' => 'in_stock',
                'category_id' => 2,
                'brand' => 'Phka Cosmetics',
                'is_active' => true,
                'rating' => 4.2,
                'review_count' => 42,
            ],
            [
                'name' => 'Argan Oil Hair Treatment',
                'slug' => 'argan-oil-hair-treatment',
                'sku' => 'AOHT001',
                'description' => 'Nourishing treatment for damaged hair',
                'short_description' => 'Restores shine and softness',
                'price' => 32.00,
                'stock_quantity' => 50,
                'stock_status' => 'in_stock',
                'category_id' => 3,
                'brand' => 'Phka Hair Care',
                'is_active' => true,
                'rating' => 4.7,
                'review_count' => 18,
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['sku' => $product['sku']],
                array_merge($product, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create sample beauty quiz (avoid duplicates)
        DB::table('beauty_quizzes')->updateOrInsert(
            ['title' => 'Skin Type Assessment'],
            [
                'description' => 'Find your skin type and get personalized recommendations',
                'skin_type_focus' => 'all',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create sample quiz questions (avoid duplicates)
        $questions = [
            [
                'quiz_id' => 1,
                'question' => 'How does your skin feel after washing?',
                'question_type' => 'single_choice',
                'options' => json_encode(['Tight and dry', 'Normal', 'Oily', 'Shiny and greasy']),
                'sort_order' => 1,
            ],
            [
                'quiz_id' => 1,
                'question' => 'Do you experience breakouts?',
                'question_type' => 'single_choice',
                'options' => json_encode(['Never', 'Rarely', 'Sometimes', 'Frequently']),
                'sort_order' => 2,
            ],
        ];

        foreach ($questions as $question) {
            DB::table('quiz_questions')->updateOrInsert(
                ['quiz_id' => $question['quiz_id'], 'sort_order' => $question['sort_order']],
                array_merge($question, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create sample FAQs (avoid duplicates)
        $faqs = [
            [
                'category' => 'Orders',
                'question' => 'How long does shipping take?',
                'answer' => 'Standard shipping takes 3-5 business days. Express shipping is available for 1-2 business days.',
                'is_featured' => true,
            ],
            [
                'category' => 'Returns',
                'question' => 'What is your return policy?',
                'answer' => 'We accept returns within 30 days of purchase. Items must be unused and in original packaging.',
                'is_featured' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            DB::table('faqs')->updateOrInsert(
                ['question' => $faq['question']],
                array_merge($faq, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create sample store (avoid duplicates)
        DB::table('stores')->updateOrInsert(
            ['email' => 'downtown@phka.com'],
            [
                'name' => 'Phka Beauty Downtown',
                'description' => 'Our flagship store in the city center',
                'address' => '123 Beauty Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'USA',
                'phone' => '+1-555-0123',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'hours' => json_encode([
                    'monday' => '9:00 AM - 8:00 PM',
                    'tuesday' => '9:00 AM - 8:00 PM',
                    'wednesday' => '9:00 AM - 8:00 PM',
                    'thursday' => '9:00 AM - 8:00 PM',
                    'friday' => '9:00 AM - 9:00 PM',
                    'saturday' => '10:00 AM - 9:00 PM',
                    'sunday' => '11:00 AM - 7:00 PM',
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
