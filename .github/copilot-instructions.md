b# Phka Backend - AI Coding Assistant Instructions

## Project Overview
Phka is a Laravel 12-based backend API for a beauty and cosmetics e-commerce platform. It provides comprehensive e-commerce functionality with beauty-specific features including skin type matching, beauty quizzes, tutorials, and community features.

## Architecture Overview
- **Framework**: Laravel 12 with PHP 8.2+
- **Authentication**: Laravel Sanctum for API token authentication
- **Database**: SQLite (default) or MySQL, with 30+ interconnected tables
- **API**: RESTful JSON API with consistent response format
- **Frontend**: Vite-compiled assets (Tailwind CSS + Axios)
- **Testing**: Pest framework with database factories

## Key Components
- **E-commerce Core**: Products, categories, cart, orders, payments, reviews
- **Beauty Features**: Skin type quizzes, beauty tips, tutorial videos, ingredient analysis
- **Community**: User posts, comments, likes, social features
- **Support**: FAQ system, support tickets, messaging
- **User Management**: Profiles, addresses, wishlists, loyalty points

## Development Workflow

### Setup
```bash
composer run setup  # Full project initialization (install, migrate, seed, build assets)
```

### Development Server
```bash
composer run dev    # Runs: Laravel server + Queue worker + Vite dev server concurrently
```

### Testing
```bash
composer run test   # Clears config cache and runs Pest tests
php artisan test    # Direct Pest execution
```

### Database
```bash
php artisan migrate         # Run migrations
php artisan migrate:fresh   # Reset database
php artisan db:seed         # Seed database
php artisan tinker          # Interactive shell
```

## API Patterns

### Response Format
All endpoints return consistent JSON structure:
```json
{
  "success": true,
  "data": { /* payload */ },
  "message": "Optional success message"
}
```

### Authentication
- Use `Authorization: Bearer {token}` header for protected routes
- Sanctum tokens issued on login/registration
- Token refresh handled automatically

### Route Structure
- Public routes: `/api/products`, `/api/categories`
- Protected routes: `/api/auth/*`, `/api/cart/*`, `/api/orders/*`
- Beauty features: `/api/beauty/*`
- Community: `/api/community/*`
- Support: `/api/support/*`

## Code Patterns

### Models
- Use proper Eloquent relationships (`hasMany`, `belongsTo`, etc.)
- Define `$fillable`, `$casts`, and relationship methods
- Implement query scopes: `scopeActive()`, `scopeFeatured()`, `scopeInStock()`
- Use accessors for computed properties: `getCurrentPriceAttribute()`

### Controllers
- Extend `App\Http\Controllers\Api\Controller`
- Use form requests for validation: `StoreProductRequest`, `UpdateProductRequest`
- Eager load relationships: `->with(['category', 'variants'])`
- Return JSON responses with success/data structure
- Use database transactions for complex operations

### Routes
- Group related routes with prefixes: `Route::prefix('products')->group(...)`
- Apply middleware: `->middleware('auth:sanctum')`
- Use route model binding where possible
- Simple endpoints may use inline closures

### Testing
- Use Pest syntax with `it()` and `test()` functions
- Leverage `RefreshDatabase` trait
- Test JSON API responses: `->assertJson(['success' => true])`
- Use factories for test data: `Product::factory()->create()`
- Test both success and error scenarios

## Database Schema Highlights

### Core Relationships
- Users have many addresses, orders, reviews, wishlists
- Products belong to categories, have variants and ingredients
- Orders contain order items with product/variant references
- Beauty quizzes generate results with product recommendations

### Key Tables
- `users`: Extended with skin_type, loyalty_points, beauty preferences
- `products`: Complex with variants, ingredients, reviews, pricing
- `orders`: Full e-commerce order lifecycle with tracking
- `beauty_quizzes`: Skin type assessment with personalized recommendations

## Common Patterns

### Product Filtering
```php
$query = Product::active()->inStock();
if ($request->has('category_id')) {
    $query->where('category_id', $request->category_id);
}
// Additional filters: price range, brand, skin_type
```

### Pagination
```php
$products = $query->paginate(20);
return response()->json(['success' => true, 'data' => $products]);
```

### Error Handling
```php
try {
    // Operation
    DB::commit();
    return response()->json(['success' => true, 'data' => $result]);
} catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
}
```

## File Organization
- `app/Models/`: Eloquent models with relationships
- `app/Http/Controllers/Api/`: API controllers
- `app/Http/Requests/`: Form request validation classes
- `database/migrations/`: Database schema definitions
- `database/factories/`: Model factories for testing
- `tests/Feature/Api/`: API endpoint tests
- `routes/api.php`: API route definitions

## Dependencies
- **Laravel Sanctum**: API authentication
- **Pest**: Testing framework
- **Vite**: Asset compilation
- **Tailwind CSS**: Styling (if frontend work needed)
- **Axios**: HTTP client (frontend)

## Development Tips
- Always run tests after changes: `composer run test`
- Use `php artisan tinker` for quick model testing
- Check API responses in Postman using the testing guide in `docs/POSTMAN_API_TESTING_GUIDE.md`
- Database changes require migrations: `php artisan make:migration`
- New API endpoints need corresponding tests in `tests/Feature/Api/`

## Key Files to Reference
- `docs/architecture.md`: Complete database schema and relationships
- `database/schema/mysql-schema.sql`: Complete MySQL database schema
- `database/schema/er_diagram.md`: Comprehensive ER diagram of all entities
- `database/schema/er_diagram_simplified.md`: Simplified ER diagram focusing on core business entities
- `routes/api.php`: All API endpoint definitions
- `app/Models/Product.php`: Complex model with relationships and scopes
- `app/Http/Controllers/Api/ProductController.php`: Controller patterns
- `tests/Feature/Api/ProductTest.php`: Testing examples