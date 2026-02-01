# AI Development Rules for Document Tracker API

## Project Context
This is a Laravel 10.x API application for document tracking with authentication via Laravel Sanctum and Fortify.

## Mandatory Post-Task Actions
- **Always run tests after completing any task**: `php artisan test`
- Run static analysis if changes affect core logic: `./vendor/bin/phpstan analyse`
- Clear caches if config/routes/views are modified: `php artisan optimize:clear`

## Laravel Conventions & Best Practices

### Code Organization
- Use Laravel's directory structure strictly (Controllers in `app/Http/Controllers`, Models in `app/Models`, etc.)
- Keep controllers thin - move business logic to Actions, Services, or Jobs
- Use Form Requests for validation (`php artisan make:request`)
- Use Policies for authorization (`php artisan make:policy`)
- Use API Resources for response transformation (`php artisan make:resource`)

### Database
- Always create migrations for schema changes: `php artisan make:migration`
- Use factories for test data: `php artisan make:factory`
- Use seeders for initial/demo data
- Add indexes for foreign keys and frequently queried columns
- Use soft deletes where appropriate

### API Development
- All API routes go in `routes/api.php` with `/api` prefix
- Use proper HTTP status codes (200, 201, 204, 400, 401, 403, 404, 422, 500)
- Return consistent JSON responses using API Resources
- Implement proper error handling with try-catch blocks
- Use Sanctum for API authentication

### Security
- Always validate and sanitize user input
- Use Laravel's built-in CSRF protection for web routes
- Implement proper authorization checks using Policies
- Never expose sensitive data in API responses
- Use query parameter binding to prevent SQL injection
- Rate limit API endpoints appropriately

### Testing
- Write Feature tests for API endpoints
- Write Unit tests for complex business logic
- Use RefreshDatabase trait in tests
- Test both success and failure scenarios
- Test authorization and validation rules
- Aim for meaningful test coverage (not just 100%)

### Code Style
- Follow PSR-12 coding standards
- Use type hints for parameters and return types
- Use strict types: `declare(strict_types=1);`
- Write descriptive variable and method names
- Add PHPDoc blocks for complex methods
- Use early returns to reduce nesting

### Performance
- Use eager loading to avoid N+1 queries: `with()`
- Use chunking for large datasets: `chunk()`
- Cache expensive operations appropriately
- Use database transactions for multiple related operations
- Index database columns used in WHERE, ORDER BY, and JOIN clauses

### Documentation
- Update API documentation when endpoints change
- Add comments for complex business logic
- Keep README.md up to date with setup instructions
- Document environment variables in `.env.example`

## Commands Reference
```bash
# Testing
php artisan test                          # Run all tests
php artisan test --filter TestName        # Run specific test
php artisan test --coverage              # Generate coverage report

# Database
php artisan migrate                       # Run migrations
php artisan migrate:fresh --seed         # Fresh migration with seeding
php artisan db:seed                      # Run seeders

# Cache Management
php artisan optimize:clear               # Clear all caches
php artisan config:cache                 # Cache configuration
php artisan route:cache                  # Cache routes

# Code Generation
php artisan make:controller NameController --api --resource
php artisan make:model Name -mfsc        # Model with migration, factory, seeder, controller
php artisan make:request NameRequest
php artisan make:resource NameResource
php artisan make:policy NamePolicy --model=Name
php artisan make:test NameTest           # Feature test
php artisan make:test NameTest --unit    # Unit test
```

## Error Handling Pattern
```php
try {
    // Business logic
    return response()->json(['data' => $result], 200);
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    return response()->json(['message' => 'Resource not found'], 404);
} catch (\Illuminate\Validation\ValidationException $e) {
    return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
} catch (\Exception $e) {
    \Log::error($e->getMessage());
    return response()->json(['message' => 'An error occurred'], 500);
}
```

## When Making Changes
1. Understand the full context before making changes
2. Check existing patterns in the codebase and follow them
3. Create/update tests for your changes
4. Run tests to ensure nothing breaks
5. Update documentation if needed
6. Clear caches if configuration changes were made

## Things to Avoid
- Don't use raw SQL queries unless absolutely necessary
- Don't put business logic in routes files
- Don't skip authorization checks
- Don't commit `.env` file or sensitive data
- Don't use mass assignment without `$fillable` or `$guarded`
- Don't ignore validation errors
- Don't use `dd()` or `dump()` in production code
