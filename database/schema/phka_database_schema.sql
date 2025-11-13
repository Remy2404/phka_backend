-- ============================================================================
-- Phka E-Commerce Android App - Complete Database Schema
-- ============================================================================
-- Generated based on comprehensive analysis of all 60+ screen designs
-- Database: MySQL
-- Version: 1.0
-- Date: 2025-01-13
-- ============================================================================

-- ==================== USERS & AUTHENTICATION =================================
-- ============================================================================

-- Users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    full_name TEXT,
    avatar_url TEXT,
    phone TEXT,
    date_of_birth DATE,
    gender TEXT CHECK(gender IN ('male', 'female', 'other', 'prefer_not_to_say')),
    
    -- Beauty quiz specific fields
    skin_type TEXT CHECK(skin_type IN ('normal', 'oily', 'dry', 'combination', 'sensitive')),
    skin_concerns TEXT, -- JSON array: ["acne", "aging", "dark_spots"]
    beauty_preferences TEXT, -- JSON object with user preferences
    
    -- Loyalty program
    loyalty_points INTEGER DEFAULT 0,
    loyalty_tier TEXT DEFAULT 'bronze' CHECK(loyalty_tier IN ('bronze', 'silver', 'gold', 'platinum')),
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME,
    is_active BOOLEAN DEFAULT 1,
    is_verified BOOLEAN DEFAULT 0,
    
    -- Social auth
    google_id TEXT UNIQUE,
    apple_id TEXT UNIQUE,
    facebook_id TEXT UNIQUE
);

-- User sessions
CREATE TABLE user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    device_info TEXT, -- JSON: {"type":"android","model":"Pixel 6"}
    ip_address TEXT,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================================
-- PRODUCT CATALOG
-- ============================================================================

-- Categories (hierarchical structure)
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    icon_url TEXT,
    image_url TEXT,
    banner_url TEXT,
    parent_id INTEGER,
    sort_order INTEGER DEFAULT 0,
    item_count INTEGER DEFAULT 0,
    is_featured BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Brands
CREATE TABLE brands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    logo_url TEXT,
    banner_url TEXT,
    website_url TEXT,
    is_featured BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    short_description TEXT,
    brand_id INTEGER,
    category_id INTEGER NOT NULL,
    
    -- Pricing
    base_price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    is_on_sale BOOLEAN DEFAULT 0,
    discount_percentage INTEGER DEFAULT 0,
    
    -- Inventory
    stock_quantity INTEGER DEFAULT 0,
    sku TEXT UNIQUE,
    barcode TEXT,
    
    -- Physical attributes
    weight DECIMAL(8,3),
    dimensions TEXT, -- JSON: {"length":10,"width":5,"height":2,"unit":"cm"}
    
    -- Product details
    ingredients TEXT,
    how_to_use TEXT,
    benefits TEXT,
    warnings TEXT,
    
    -- Skin compatibility
    skin_types TEXT, -- JSON array: ["dry","oily","normal"]
    skin_concerns TEXT, -- JSON array: ["acne","aging"]
    
    -- Product attributes
    is_vegan BOOLEAN DEFAULT 0,
    is_cruelty_free BOOLEAN DEFAULT 0,
    is_organic BOOLEAN DEFAULT 0,
    is_paraben_free BOOLEAN DEFAULT 0,
    is_sulfate_free BOOLEAN DEFAULT 0,
    
    -- Ratings & popularity
    rating DECIMAL(3,2) DEFAULT 0.0,
    review_count INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    purchase_count INTEGER DEFAULT 0,
    
    -- Status flags
    is_featured BOOLEAN DEFAULT 0,
    is_new_arrival BOOLEAN DEFAULT 0,
    is_best_seller BOOLEAN DEFAULT 0,
    is_limited_edition BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    published_at DATETIME,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
);

-- Product variants (size, color, scent, etc.)
CREATE TABLE product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    variant_type TEXT NOT NULL, -- 'size', 'color', 'scent', 'volume'
    variant_value TEXT NOT NULL, -- '30ml', 'Rose', 'Red', etc.
    
    -- Variant specific pricing
    price_modifier DECIMAL(8,2) DEFAULT 0.0,
    
    -- Variant inventory
    stock_quantity INTEGER DEFAULT 0,
    sku TEXT UNIQUE,
    
    -- Visual attributes (for color variants)
    color_hex TEXT,
    image_url TEXT,
    
    is_available BOOLEAN DEFAULT 1,
    is_active BOOLEAN DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product images
CREATE TABLE product_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    variant_id INTEGER,
    image_url TEXT NOT NULL,
    alt_text TEXT,
    is_primary BOOLEAN DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
);

-- Product features (360° View, AR Try-On, Size Guide, etc.)
CREATE TABLE product_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    feature_type TEXT NOT NULL, -- '360_view', 'ar_try_on', 'size_guide', 'video'
    feature_name TEXT NOT NULL,
    feature_description TEXT,
    feature_data TEXT, -- JSON with feature-specific data
    icon_url TEXT,
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Product tags
CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    type TEXT DEFAULT 'general' CHECK(type IN ('general', 'benefit', 'ingredient', 'skin_concern')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Product-Tag relationship
CREATE TABLE product_tags (
    product_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY (product_id, tag_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- ============================================================================
-- REVIEWS & RATINGS
-- ============================================================================

-- Reviews
CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
    title TEXT,
    comment TEXT,
    
    -- Review metadata
    is_verified_purchase BOOLEAN DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    not_helpful_count INTEGER DEFAULT 0,
    
    -- Moderation
    is_approved BOOLEAN DEFAULT 0,
    is_reported BOOLEAN DEFAULT 0,
    moderation_notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(product_id, user_id)
);

-- Review images
CREATE TABLE review_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    review_id INTEGER NOT NULL,
    image_url TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
);

-- Review helpful votes
CREATE TABLE review_votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    review_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    is_helpful BOOLEAN NOT NULL, -- true = helpful, false = not helpful
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(review_id, user_id)
);

-- ============================================================================
-- SHOPPING CART & WISHLIST
-- ============================================================================

-- Shopping cart
CREATE TABLE cart_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    variant_id INTEGER,
    quantity INTEGER NOT NULL DEFAULT 1 CHECK(quantity > 0),
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    UNIQUE(user_id, product_id, variant_id)
);

-- Wishlist
CREATE TABLE wishlist_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(user_id, product_id)
);

-- Product comparison
CREATE TABLE product_comparisons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(user_id, product_id)
);

-- ============================================================================
-- ADDRESSES & LOCATIONS
-- ============================================================================

-- Addresses
CREATE TABLE addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    address_type TEXT DEFAULT 'shipping' CHECK(address_type IN ('shipping', 'billing')),
    
    -- Contact info
    full_name TEXT NOT NULL,
    phone TEXT,
    
    -- Address details
    address_line_1 TEXT NOT NULL,
    address_line_2 TEXT,
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    postal_code TEXT NOT NULL,
    country TEXT NOT NULL DEFAULT 'US',
    
    -- Location coordinates
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    
    -- Flags
    is_default BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Store locations
CREATE TABLE store_locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    address_line_1 TEXT NOT NULL,
    address_line_2 TEXT,
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    postal_code TEXT NOT NULL,
    country TEXT NOT NULL DEFAULT 'US',
    phone TEXT,
    email TEXT,
    
    -- Location
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    
    -- Operating hours (JSON)
    hours TEXT, -- {"monday":"9-5", "tuesday":"9-5", ...}
    
    -- Amenities
    amenities TEXT, -- JSON array: ["parking", "wifi", "beauty_services"]
    
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- PAYMENT METHODS
-- ============================================================================

-- Payment methods
CREATE TABLE payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('credit_card', 'debit_card', 'paypal', 'apple_pay', 'google_pay')),
    provider TEXT, -- 'visa', 'mastercard', 'amex', etc.
    
    -- Card details (tokenized)
    token TEXT, -- Payment gateway token
    last_four TEXT,
    expiry_month INTEGER,
    expiry_year INTEGER,
    cardholder_name TEXT,
    
    -- Billing address
    billing_address_id INTEGER,
    
    is_default BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (billing_address_id) REFERENCES addresses(id) ON DELETE SET NULL
);

-- ============================================================================
-- ORDERS & TRANSACTIONS
-- ============================================================================

-- Orders
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    
    -- Order status
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN (
        'pending', 'confirmed', 'processing', 'packed', 
        'shipped', 'out_for_delivery', 'delivered', 
        'cancelled', 'refunded', 'failed'
    )),
    
    -- Pricing
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.0,
    shipping_amount DECIMAL(10,2) DEFAULT 0.0,
    discount_amount DECIMAL(10,2) DEFAULT 0.0,
    loyalty_points_used INTEGER DEFAULT 0,
    loyalty_discount DECIMAL(10,2) DEFAULT 0.0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    
    -- Addresses
    shipping_address_id INTEGER,
    billing_address_id INTEGER,
    
    -- Shipping details
    shipping_method TEXT,
    tracking_number TEXT,
    carrier TEXT,
    estimated_delivery DATE,
    
    -- Payment
    payment_method_id INTEGER,
    payment_status TEXT DEFAULT 'pending' CHECK(payment_status IN (
        'pending', 'authorized', 'paid', 'failed', 'refunded'
    )),
    transaction_id TEXT,
    
    -- Order notes
    customer_notes TEXT,
    admin_notes TEXT,
    
    -- Loyalty points earned
    loyalty_points_earned INTEGER DEFAULT 0,
    
    -- Timestamps
    ordered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME,
    shipped_at DATETIME,
    delivered_at DATETIME,
    cancelled_at DATETIME,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE SET NULL,
    FOREIGN KEY (billing_address_id) REFERENCES addresses(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL
);

-- Order items
CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    variant_id INTEGER,
    
    -- Snapshot of product details at order time
    product_name TEXT NOT NULL,
    product_sku TEXT,
    variant_details TEXT, -- JSON: {"size":"30ml","color":"Rose"}
    
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    
    -- Review tracking
    is_reviewed BOOLEAN DEFAULT 0,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Order status history
CREATE TABLE order_status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ============================================================================
-- PROMOTIONS & DISCOUNTS
-- ============================================================================

-- Promo codes
CREATE TABLE promo_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    description TEXT,
    
    -- Discount details
    discount_type TEXT NOT NULL CHECK(discount_type IN ('percentage', 'fixed_amount', 'free_shipping')),
    discount_value DECIMAL(10,2) NOT NULL,
    
    -- Conditions
    minimum_order DECIMAL(10,2) DEFAULT 0.0,
    maximum_discount DECIMAL(10,2),
    applicable_categories TEXT, -- JSON array of category IDs
    applicable_products TEXT, -- JSON array of product IDs
    
    -- Usage limits
    usage_limit INTEGER,
    usage_limit_per_user INTEGER,
    usage_count INTEGER DEFAULT 0,
    
    -- Validity
    valid_from DATETIME,
    valid_until DATETIME,
    
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Promo code usage
CREATE TABLE promo_code_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    promo_code_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    order_id INTEGER,
    discount_applied DECIMAL(10,2) NOT NULL,
    used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Banners & Promotions
CREATE TABLE banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    image_url TEXT NOT NULL,
    mobile_image_url TEXT,
    
    -- Link configuration
    link_type TEXT CHECK(link_type IN ('category', 'product', 'url', 'none')),
    link_target TEXT, -- category_id, product_id, or URL
    
    -- Display settings
    position TEXT DEFAULT 'home' CHECK(position IN ('home', 'category', 'product', 'cart')),
    sort_order INTEGER DEFAULT 0,
    
    -- Validity
    valid_from DATETIME,
    valid_until DATETIME,
    
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- PRODUCT BUNDLES
-- ============================================================================

-- Product bundles
CREATE TABLE product_bundles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    image_url TEXT,
    
    -- Pricing
    bundle_price DECIMAL(10,2) NOT NULL,
    original_total DECIMAL(10,2),
    savings_amount DECIMAL(10,2) DEFAULT 0.0,
    
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bundle items
CREATE TABLE bundle_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bundle_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    variant_id INTEGER,
    quantity INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY (bundle_id) REFERENCES product_bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- ============================================================================
-- GIFT CARDS
-- ============================================================================

-- Gift cards
CREATE TABLE gift_cards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    
    -- Balance
    initial_balance DECIMAL(10,2) NOT NULL,
    current_balance DECIMAL(10,2) NOT NULL,
    
    -- Purchase details
    purchased_by INTEGER,
    recipient_email TEXT,
    recipient_name TEXT,
    sender_message TEXT,
    
    -- Delivery
    delivery_method TEXT DEFAULT 'email' CHECK(delivery_method IN ('email', 'physical')),
    sent_at DATETIME,
    
    -- Validity
    expiry_date DATE,
    
    is_active BOOLEAN DEFAULT 1,
    is_redeemed BOOLEAN DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (purchased_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Gift card transactions
CREATE TABLE gift_card_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gift_card_id INTEGER NOT NULL,
    order_id INTEGER,
    transaction_type TEXT NOT NULL CHECK(transaction_type IN ('purchase', 'redeem', 'refund')),
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gift_card_id) REFERENCES gift_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- ============================================================================
-- BEAUTY QUIZ & RECOMMENDATIONS
-- ============================================================================

-- Beauty quiz questions
CREATE TABLE quiz_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question TEXT NOT NULL,
    question_type TEXT NOT NULL CHECK(question_type IN ('single_choice', 'multiple_choice', 'scale', 'text')),
    options TEXT, -- JSON array of options
    category TEXT CHECK(category IN ('skin_type', 'skin_concerns', 'preferences', 'goals')),
    help_text TEXT,
    sort_order INTEGER DEFAULT 0,
    is_required BOOLEAN DEFAULT 1,
    is_active BOOLEAN DEFAULT 1
);

-- Quiz results
CREATE TABLE quiz_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- Results
    skin_type TEXT,
    skin_concerns TEXT, -- JSON array
    beauty_goals TEXT, -- JSON array
    preferences TEXT, -- JSON object
    
    -- Recommendations
    recommended_products TEXT, -- JSON array of product IDs
    recommended_routine TEXT, -- JSON structured routine
    
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Quiz answers
CREATE TABLE quiz_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_result_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    answer TEXT NOT NULL, -- JSON for multiple choice, text for others
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_result_id) REFERENCES quiz_results(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- ============================================================================
-- LOYALTY PROGRAM
-- ============================================================================

-- Loyalty rewards
CREATE TABLE loyalty_rewards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    image_url TEXT,
    
    -- Cost
    points_required INTEGER NOT NULL,
    
    -- Reward type
    reward_type TEXT NOT NULL CHECK(reward_type IN ('discount', 'free_shipping', 'free_product', 'voucher')),
    reward_value TEXT, -- JSON with reward-specific data
    
    -- Availability
    stock_quantity INTEGER,
    usage_limit_per_user INTEGER,
    
    -- Validity
    valid_from DATETIME,
    valid_until DATETIME,
    
    is_active BOOLEAN DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Loyalty transactions
CREATE TABLE loyalty_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    transaction_type TEXT NOT NULL CHECK(transaction_type IN ('earn', 'redeem', 'expire', 'refund', 'bonus')),
    points INTEGER NOT NULL,
    
    -- Source/reason
    source_type TEXT, -- 'purchase', 'review', 'referral', 'reward_redemption', etc.
    source_id INTEGER, -- order_id, review_id, etc.
    
    -- Balances
    points_before INTEGER NOT NULL,
    points_after INTEGER NOT NULL,
    
    description TEXT,
    expires_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Redeemed rewards
CREATE TABLE redeemed_rewards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    reward_id INTEGER NOT NULL,
    order_id INTEGER,
    points_spent INTEGER NOT NULL,
    reward_code TEXT UNIQUE,
    
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'used', 'expired', 'cancelled')),
    
    expires_at DATETIME,
    redeemed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES loyalty_rewards(id) ON DELETE RESTRICT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- ============================================================================
-- REFERRAL PROGRAM
-- ============================================================================

-- Referrals
CREATE TABLE referrals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    referrer_user_id INTEGER NOT NULL,
    referred_email TEXT NOT NULL,
    referred_user_id INTEGER,
    
    -- Referral code
    referral_code TEXT UNIQUE NOT NULL,
    
    -- Status
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'registered', 'completed')),
    
    -- Rewards
    referrer_reward_points INTEGER DEFAULT 0,
    referred_reward_points INTEGER DEFAULT 0,
    referrer_reward_given BOOLEAN DEFAULT 0,
    referred_reward_given BOOLEAN DEFAULT 0,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    registered_at DATETIME,
    completed_at DATETIME,
    
    FOREIGN KEY (referrer_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================================
-- SEARCH & FILTERS
-- ============================================================================

-- Search history
CREATE TABLE search_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    query TEXT NOT NULL,
    filters TEXT, -- JSON of applied filters
    result_count INTEGER,
    clicked_product_id INTEGER,
    searched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (clicked_product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Trending searches (aggregated view)
CREATE TABLE trending_searches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    query TEXT UNIQUE NOT NULL,
    search_count INTEGER DEFAULT 1,
    last_searched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- USER ACTIVITY TRACKING
-- ============================================================================

-- Recently viewed products
CREATE TABLE recently_viewed (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Price alerts
CREATE TABLE price_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    target_price DECIMAL(10,2) NOT NULL,
    current_price DECIMAL(10,2),
    
    is_triggered BOOLEAN DEFAULT 0,
    is_notified BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    triggered_at DATETIME,
    notified_at DATETIME,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(user_id, product_id)
);

-- ============================================================================
-- NOTIFICATIONS
-- ============================================================================

-- Notifications
CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- Notification details
    type TEXT NOT NULL CHECK(type IN (
        'order_update', 'price_alert', 'promotion', 'new_arrival',
        'back_in_stock', 'review_response', 'loyalty_reward',
        'abandoned_cart', 'system'
    )),
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    data TEXT, -- JSON additional data
    
    -- Linking
    link_type TEXT, -- 'order', 'product', 'category', 'url'
    link_id TEXT,
    
    -- Priority
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low', 'normal', 'high', 'urgent')),
    
    -- Status
    is_read BOOLEAN DEFAULT 0,
    is_archived BOOLEAN DEFAULT 0,
    
    -- Delivery
    send_push BOOLEAN DEFAULT 1,
    send_email BOOLEAN DEFAULT 0,
    push_sent_at DATETIME,
    email_sent_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notification preferences
CREATE TABLE notification_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- Channel preferences
    push_enabled BOOLEAN DEFAULT 1,
    email_enabled BOOLEAN DEFAULT 1,
    
    -- Type preferences
    order_updates BOOLEAN DEFAULT 1,
    price_alerts BOOLEAN DEFAULT 1,
    promotions BOOLEAN DEFAULT 1,
    new_arrivals BOOLEAN DEFAULT 1,
    back_in_stock BOOLEAN DEFAULT 1,
    review_responses BOOLEAN DEFAULT 1,
    loyalty_rewards BOOLEAN DEFAULT 1,
    
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id)
);

-- ============================================================================
-- CONTENT & SUPPORT
-- ============================================================================

-- Beauty tips articles
CREATE TABLE beauty_tips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image_url TEXT,
    
    -- Categorization
    category TEXT CHECK(category IN ('skincare', 'makeup', 'haircare', 'general')),
    tags TEXT, -- JSON array
    
    -- Related products
    related_products TEXT, -- JSON array of product IDs
    
    -- Metadata
    author TEXT,
    read_time INTEGER, -- in minutes
    view_count INTEGER DEFAULT 0,
    
    is_featured BOOLEAN DEFAULT 0,
    is_published BOOLEAN DEFAULT 0,
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tutorial videos
CREATE TABLE tutorial_videos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    video_url TEXT NOT NULL,
    thumbnail_url TEXT,
    duration INTEGER, -- in seconds
    
    -- Categorization
    category TEXT,
    difficulty_level TEXT CHECK(difficulty_level IN ('beginner', 'intermediate', 'advanced')),
    
    -- Related content
    related_products TEXT, -- JSON array of product IDs
    
    view_count INTEGER DEFAULT 0,
    like_count INTEGER DEFAULT 0,
    
    is_featured BOOLEAN DEFAULT 0,
    is_published BOOLEAN DEFAULT 0,
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- FAQ
CREATE TABLE faqs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category TEXT CHECK(category IN ('general', 'orders', 'shipping', 'returns', 'products', 'account')),
    sort_order INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Support tickets (Live chat messages)
CREATE TABLE support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_number TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    
    subject TEXT NOT NULL,
    category TEXT CHECK(category IN ('general', 'order', 'product', 'technical', 'billing')),
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low', 'normal', 'high', 'urgent')),
    status TEXT DEFAULT 'open' CHECK(status IN ('open', 'in_progress', 'resolved', 'closed')),
    
    assigned_to INTEGER, -- admin/support user ID
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    closed_at DATETIME,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Support messages
CREATE TABLE support_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    sender_type TEXT NOT NULL CHECK(sender_type IN ('user', 'support')),
    sender_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    attachment_url TEXT,
    is_read BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

-- ============================================================================
-- COMMUNITY & SOCIAL
-- ============================================================================

-- Community posts
CREATE TABLE community_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    images TEXT, -- JSON array of image URLs
    
    -- Categorization
    category TEXT CHECK(category IN ('discussion', 'review', 'tutorial', 'question')),
    tags TEXT, -- JSON array
    
    -- Engagement
    like_count INTEGER DEFAULT 0,
    comment_count INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    
    -- Moderation
    is_approved BOOLEAN DEFAULT 0,
    is_reported BOOLEAN DEFAULT 0,
    is_pinned BOOLEAN DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Community comments
CREATE TABLE community_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    parent_comment_id INTEGER,
    content TEXT NOT NULL,
    
    like_count INTEGER DEFAULT 0,
    
    is_approved BOOLEAN DEFAULT 0,
    is_reported BOOLEAN DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES community_comments(id) ON DELETE CASCADE
);

-- ============================================================================
-- APP CONFIGURATION & SETTINGS
-- ============================================================================

-- App settings
CREATE TABLE app_settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    type TEXT DEFAULT 'string' CHECK(type IN ('string', 'number', 'boolean', 'json')),
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User preferences
CREATE TABLE user_preferences (
    user_id INTEGER PRIMARY KEY,
    
    -- UI preferences
    theme TEXT DEFAULT 'system' CHECK(theme IN ('light', 'dark', 'system')),
    language TEXT DEFAULT 'en',
    currency TEXT DEFAULT 'USD',
    
    -- Privacy
    allow_personalized_ads BOOLEAN DEFAULT 1,
    allow_analytics BOOLEAN DEFAULT 1,
    allow_location BOOLEAN DEFAULT 0,
    
    -- Communication
    newsletter_subscribed BOOLEAN DEFAULT 0,
    sms_notifications BOOLEAN DEFAULT 0,
    
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================================
-- ANALYTICS & METRICS
-- ============================================================================

-- Product analytics (for recommendations)
CREATE TABLE product_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    event_type TEXT NOT NULL CHECK(event_type IN ('view', 'add_to_cart', 'add_to_wishlist', 'purchase')),
    user_id INTEGER,
    session_id TEXT,
    source TEXT, -- 'home', 'category', 'search', 'recommendation'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================================

-- Users indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_loyalty_points ON users(loyalty_points DESC);

-- Products indexes
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_brand ON products(brand_id);
CREATE INDEX idx_products_rating ON products(rating DESC);
CREATE INDEX idx_products_featured ON products(is_featured) WHERE is_featured = 1;
CREATE INDEX idx_products_price ON products(base_price);
CREATE INDEX idx_products_created ON products(created_at DESC);

-- Reviews indexes
CREATE INDEX idx_reviews_product ON reviews(product_id);
CREATE INDEX idx_reviews_user ON reviews(user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating DESC);

-- Cart indexes
CREATE INDEX idx_cart_user ON cart_items(user_id);
CREATE INDEX idx_cart_product ON cart_items(product_id);

-- Wishlist indexes
CREATE INDEX idx_wishlist_user ON wishlist_items(user_id);
CREATE INDEX idx_wishlist_product ON wishlist_items(product_id);

-- Orders indexes
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_number ON orders(order_number);
CREATE INDEX idx_orders_date ON orders(ordered_at DESC);

-- Order items indexes
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);

-- Addresses indexes
CREATE INDEX idx_addresses_user ON addresses(user_id);

-- Payment methods indexes
CREATE INDEX idx_payment_methods_user ON payment_methods(user_id);

-- Search history indexes
CREATE INDEX idx_search_history_user ON search_history(user_id);
CREATE INDEX idx_search_history_date ON search_history(searched_at DESC);

-- Recently viewed indexes
CREATE INDEX idx_recently_viewed_user ON recently_viewed(user_id);
CREATE INDEX idx_recently_viewed_date ON recently_viewed(viewed_at DESC);

-- Price alerts indexes
CREATE INDEX idx_price_alerts_user ON price_alerts(user_id);
CREATE INDEX idx_price_alerts_product ON price_alerts(product_id);
CREATE INDEX idx_price_alerts_active ON price_alerts(is_active) WHERE is_active = 1;

-- Notifications indexes
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_notifications_date ON notifications(created_at DESC);

-- Product variants indexes
CREATE INDEX idx_product_variants_product ON product_variants(product_id);

-- Product images indexes
CREATE INDEX idx_product_images_product ON product_images(product_id);

-- Loyalty indexes
CREATE INDEX idx_loyalty_transactions_user ON loyalty_transactions(user_id);
CREATE INDEX idx_loyalty_transactions_date ON loyalty_transactions(created_at DESC);

-- Analytics indexes
CREATE INDEX idx_product_analytics_product ON product_analytics(product_id);
CREATE INDEX idx_product_analytics_user ON product_analytics(user_id);
CREATE INDEX idx_product_analytics_date ON product_analytics(created_at DESC);

-- ============================================================================
-- INITIAL DATA / SEED DATA
-- ============================================================================

-- Insert default app settings
INSERT INTO app_settings (key, value, type, description) VALUES
('app_version', '1.0.0', 'string', 'Current app version'),
('min_order_amount', '10.00', 'number', 'Minimum order amount for checkout'),
('free_shipping_threshold', '50.00', 'number', 'Order amount for free shipping'),
('loyalty_points_per_dollar', '1', 'number', 'Points earned per dollar spent'),
('max_cart_items', '20', 'number', 'Maximum items allowed in cart'),
('review_moderation_enabled', 'true', 'boolean', 'Enable review moderation'),
('community_moderation_enabled', 'true', 'boolean', 'Enable community post moderation');

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
