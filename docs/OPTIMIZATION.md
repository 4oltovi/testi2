# ⚡ ОПТИМИЗАТСИЯ БАРОИ 500-600 КОРБАРИ ҲАМЗАМОН

## 1. Кеш (Redis)

```php
// config/cache.php
'default' => env('CACHE_STORE', 'redis'),

// Истифодаи кеш дар код:
Cache::remember('dashboard_stats', 300, fn() => [...]);
Cache::remember("student_gpa:{$studentId}", 600, fn() => ...);
Cache::remember("setting:{$key}", 3600, fn() => ...);
```

## 2. Сессия (Redis)

```env
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
```

## 3. Навбат (Queue Workers)

```env
QUEUE_CONNECTION=redis
```

Амалҳои вазнин ба background jobs гузаранда:
- Ҳисоби GPA барои гурӯҳ
- Содироти PDF/Excel
- Backup
- Огоҳномаҳо

## 4. Database Optimization

### Индексҳо
Тамоми migration-ҳо дорои индексҳои зарурӣ:
- Foreign key indexes (автоматӣ)
- Composite indexes барои query-ҳои мураккаб
- Index-ҳо бар status, is_active, created_at
- Unique constraints

### Query Optimization
- Eager Loading (`with()`) дар ҳама controller-ҳо
- `withCount()` ба ҷойи count()-ҳои алоҳида
- Pagination дар ҳама рӯйхатҳо (25-50 элемент)
- `selectRaw()` барои ҳисобҳои мураккаб

### Мисолҳо:
```php
// Бе оптимизатсия (N+1 problem):
$students = Student::all();
foreach ($students as $s) { $s->group->name; } // N+1 queries!

// Бо оптимизатсия:
$students = Student::with(['user', 'group', 'specialty'])->paginate(25);
```

## 5. Nginx Configuration

```nginx
worker_processes auto;
worker_connections 1024;

server {
    listen 80;
    server_name donishor.tj;
    root /var/www/donishor/public;
    index index.php;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
    gzip_min_length 1000;

    # Static files cache
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

## 6. PHP-FPM Configuration

```ini
; /etc/php/8.3/fpm/pool.d/donishor.conf
[donishor]
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000

; OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  ; Production only
```

## 7. MySQL Configuration

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 200
query_cache_type = 0  ; MySQL 8 doesn't use query cache
sort_buffer_size = 4M
join_buffer_size = 4M
tmp_table_size = 64M
max_heap_table_size = 64M
```

## 8. Redis Configuration

```ini
maxmemory 512mb
maxmemory-policy allkeys-lru
```

## 9. Laravel Optimization Commands

```bash
# Production deployment:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Queue worker:
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

## 10. Rate Limiting

```php
// 5 login attempts per minute
RateLimiter::for('login', fn(Request $r) => Limit::perMinute(5)->by($r->ip()));

// 60 API requests per minute
RateLimiter::for('api', fn(Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
```

## 11. Monitoring

- **Server**: Prometheus + Grafana
- **Application**: Laravel Telescope (dev), Laravel Pulse (prod)
- **Database**: MySQL Slow Query Log
- **Errors**: Sentry/Bugsnag

## 12. Backup Strategy

```bash
# Ҳар рӯз backup:
0 2 * * * php artisan backup:run
# Нигоҳдории 30 рӯз:
0 3 * * 0 php artisan backup:clean
```

## Натиҷаи интизорӣ

Бо ин танзимот система бояд:
- ≤ 200ms response time барои саҳифаҳои оддӣ
- ≤ 500ms барои саҳифаҳои мураккаб (журнал, рейтинг)
- ≤ 2s барои содирот (PDF/Excel) бо background job
- 500-600 корбари ҳамзамон бе мушкилот
