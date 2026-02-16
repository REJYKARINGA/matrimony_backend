# API Performance Optimization Guide

## ✅ Completed Optimizations

### 1. Database Indexes (COMPLETED)
Added comprehensive indexes to all frequently queried columns:

**Run migration:**
```bash
php artisan migrate
```

**Key indexes added:**
- `users`: status, deleted_at, role, last_login
- `user_profiles`: user_id, gender, religion_id, caste_id, education_id, occupation_id, is_active_verified, date_of_birth, height, annual_income, district, latitude, longitude
- `interests_sent`: sender_id, receiver_id, status
- `contact_unlocks`: user_id, unlocked_user_id
- `profile_views`: viewer_id, viewed_profile_id, viewed_at
- And many more...

**Expected improvement:** 50-80% faster queries on filtered/searched data

---

### 2. API Resources for Data Transformation (COMPLETED)
Using Laravel API Resources to:
- Hide sensitive data (password, email, phone) by default
- Only show contact info when unlocked
- Transform data consistently

**Files created:**
- `app/Http/Resources/UserResource.php`
- `app/Http/Resources/UserProfileResource.php`
- `app/Http/Resources/ProfilePhotoResource.php`
- `app/Http/Resources/ReligionResource.php`
- `app/Http/Resources/CasteResource.php`
- `app/Http/Resources/SubCasteResource.php`
- `app/Http/Resources/EducationResource.php`
- `app/Http/Resources/OccupationResource.php`
- `app/Http/Resources/FamilyDetailResource.php`

---

### 3. Eager Loading Optimization (COMPLETED)
All controllers now use eager loading to prevent N+1 queries:

```php
User::with([
    'userProfile.religionModel',
    'userProfile.casteModel',
    'userProfile.subCasteModel',
    'userProfile.educationModel',
    'userProfile.occupationModel',
    'profilePhotos'
])
```

**Expected improvement:** 60-90% reduction in database queries

---

## 🚀 Additional Optimizations to Implement

### 4. Query Caching
Cache frequently accessed data to reduce database load:

**Example - Cache suggestions for 5 minutes:**
```php
use Illuminate\Support\Facades\Cache;

public function getSuggestions(Request $request)
{
    $user = $request->user();
    $cacheKey = "suggestions_user_{$user->id}_page_{$request->page ?? 1}";
    
    return Cache::remember($cacheKey, 300, function () use ($user) {
        // Your query here
        $suggestions = User::with([...])->paginate(12);
        return UserResource::collection($suggestions);
    });
}
```

**Clear cache when data changes:**
```php
Cache::tags(['user_suggestions'])->flush();
```

---

### 5. Select Only Needed Columns
Reduce data transfer by selecting only required columns:

```php
User::with(['userProfile'])
    ->select('users.id', 'users.matrimony_id', 'users.created_at')
    ->paginate(12);
```

**Expected improvement:** 30-50% reduction in response size

---

### 6. Limit Eager Loaded Relationships
Only load necessary relationships:

```php
// Instead of loading all photos, load only primary
'profilePhotos' => function ($q) {
    $q->where('is_primary', true)->limit(1);
}
```

---

### 7. Database Query Optimization

**Use EXPLAIN to analyze queries:**
```sql
EXPLAIN SELECT * FROM users 
INNER JOIN user_profiles ON users.id = user_profiles.user_id 
WHERE users.status = 'active' 
AND user_profiles.is_active_verified = 1;
```

**Add composite indexes for frequently combined filters:**
```php
$table->index(['status', 'deleted_at']);
$table->index(['is_active_verified', 'gender']);
$table->index(['religion_id', 'caste_id']);
```

---

### 8. Redis Cache (Recommended for Production)

**Install Redis:**
```bash
composer require predis/predis
```

**Update .env:**
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Cache heavy queries:**
```php
$users = Cache::remember(
    'active_users_page_' . $page, 
    600, // 10 minutes
    function () {
        return User::where('status', 'active')->paginate(20);
    }
);
```

---

### 9. API Response Compression

**Enable gzip in .htaccess (Apache):**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/json
    AddOutputFilterByType DEFLATE text/html
</IfModule>
```

**Or in nginx:**
```nginx
gzip on;
gzip_types application/json text/html;
gzip_min_length 1000;
```

---

### 10. Flutter Frontend Optimizations

**In your Flutter app:**

1. **Use pagination properly:**
```dart
// Load more as user scrolls
_scrollController.addListener(() {
  if (_scrollController.position.pixels == 
      _scrollController.position.maxScrollExtent) {
    _loadMoreProfiles();
  }
});
```

2. **Cache images locally:**
```dart
// Use cached_network_image package
CachedNetworkImage(
  imageUrl: imageUrl,
  placeholder: (context, url) => CircularProgressIndicator(),
  errorWidget: (context, url, error) => Icon(Icons.error),
)
```

3. **Debounce API calls:**
```dart
// Prevent multiple rapid API calls
Timer? _debounce;
void _searchProfiles(String query) {
  if (_debounce?.isActive ?? false) _debounce?.cancel();
  _debounce = Timer(const Duration(milliseconds: 500), () {
    _performSearch(query);
  });
}
```

4. **Use FutureBuilder for async data:**
```dart
FutureBuilder(
  future: _loadProfiles(),
  builder: (context, snapshot) {
    if (snapshot.connectionState == ConnectionState.waiting) {
      return CircularProgressIndicator();
    }
    return ListView(...);
  },
)
```

---

## 📊 Performance Monitoring

### Enable Laravel Debugbar (Development Only)
```bash
composer require barryvdh/laravel-debugbar --dev
```

This shows:
- Query execution time
- Number of queries
- Memory usage
- Route information

### Use Laravel Telescope (Development)
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

---

## 🎯 Expected Performance Improvements

| Optimization | Before | After | Improvement |
|--------------|--------|-------|-------------|
| Database Indexes | 500ms | 100ms | 80% faster |
| Eager Loading | 2000ms | 400ms | 80% faster |
| Query Caching | 400ms | 50ms | 87% faster |
| Column Selection | 300ms | 200ms | 33% faster |
| **Combined** | **3200ms** | **~750ms** | **~76% faster** |

---

## 🔧 Maintenance Tips

1. **Clear cache regularly:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

2. **Optimize autoloader:**
```bash
composer dump-autoload --optimize
```

3. **Cache config (Production):**
```bash
php artisan config:cache
php artisan route:cache
```

4. **Monitor slow queries:**
Add to `config/database.php`:
```php
'mysql' => [
    'options' => [
        PDO::ATTR_TIMEOUT => 5, // 5 second timeout
    ],
],
```

---

## 📝 Next Steps

1. ✅ Run the migration (DONE)
2. Test API endpoints for speed improvement
3. Implement Redis caching for production
4. Add image caching in Flutter app
5. Monitor slow queries with Telescope
6. Consider implementing full-text search for large datasets
7. Add CDN for static assets (profile pictures)

---

## 🆘 Troubleshooting

**If API is still slow:**

1. Check for N+1 queries with Laravel Debugbar
2. Verify indexes are being used with EXPLAIN
3. Check server resources (CPU, RAM, Disk I/O)
4. Review database connection pool settings
5. Consider database query optimization
6. Check network latency between server and database

---

For more help, check Laravel documentation:
- [Query Optimization](https://laravel.com/docs/queries)
- [Caching](https://laravel.com/docs/cache)
- [Eloquent Performance](https://laravel.com/docs/eloquent-relationships)
