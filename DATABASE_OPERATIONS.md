# Database Operations Guide

## Quick Reference

### Local Development

#### Fresh Database Setup
```bash
# Create fresh database with all migrations
php artisan migrate:fresh

# Fresh database with seeding
php artisan migrate:fresh --seed
```

#### Run Migrations
```bash
# Run all pending migrations
php artisan migrate

# Run specific migration
php artisan migrate --path=database/migrations/2026_01_20_000000_create_profile_tables.php

# Run migrations for specific database
php artisan migrate --database=mysql
```

#### Rollback Migrations
```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Rollback and re-run
php artisan migrate:refresh

# Rollback and re-run with seeding
php artisan migrate:refresh --seed
```

#### Check Migration Status
```bash
# Show migration status
php artisan migrate:status

# Show pending migrations
php artisan migrate:status --pending
```

### Production Deployment (Railway)

#### Automatic Migrations
Migrations run automatically during deployment via the `release` phase in `Procfile`:
```
release: php artisan migrate --force
```

#### Manual Migration (if needed)
```bash
# Run migrations on Railway
railway run php artisan migrate --force

# Rollback on Railway
railway run php artisan migrate:rollback --force
```

#### Check Production Database
```bash
# SSH into Railway container
railway shell

# Check migration status
php artisan migrate:status

# Check specific table
php artisan tinker
>>> Schema::getColumns('users')
```

## Database Inspection

### Using Tinker
```bash
php artisan tinker

# List all tables
>>> Schema::getTables()

# Get columns for a table
>>> Schema::getColumns('users')

# Check if table exists
>>> Schema::hasTable('users')

# Check if column exists
>>> Schema::hasColumn('users', 'email')

# Get table indexes
>>> Schema::getIndexes('users')

# Get foreign keys
>>> Schema::getForeignKeys('users')
```

### Using Artisan
```bash
# Show all tables
php artisan db:show

# Show specific table
php artisan db:table users

# Show table structure
php artisan db:table users --json
```

### Using MySQL CLI
```bash
# Connect to database
mysql -h localhost -u root -p clinforce_api

# Show tables
SHOW TABLES;

# Show table structure
DESCRIBE users;
DESC users;

# Show create statement
SHOW CREATE TABLE users;

# Show indexes
SHOW INDEXES FROM users;

# Show foreign keys
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'users' AND REFERENCED_TABLE_NAME IS NOT NULL;
```

## Common Tasks

### Add a New Column
```bash
# Generate migration
php artisan make:migration add_phone_to_users_table

# Edit migration file
# database/migrations/YYYY_MM_DD_HHMMSS_add_phone_to_users_table.php

# Run migration
php artisan migrate
```

### Create New Table
```bash
# Generate migration
php artisan make:migration create_posts_table

# Edit migration file
# database/migrations/YYYY_MM_DD_HHMMSS_create_posts_table.php

# Run migration
php artisan migrate
```

### Modify Column
```bash
# Generate migration
php artisan make:migration modify_email_in_users_table

# Edit migration file with change() method
# database/migrations/YYYY_MM_DD_HHMMSS_modify_email_in_users_table.php

# Run migration
php artisan migrate
```

### Drop Column
```bash
# Generate migration
php artisan make:migration drop_phone_from_users_table

# Edit migration file
# database/migrations/YYYY_MM_DD_HHMMSS_drop_phone_from_users_table.php

# Run migration
php artisan migrate
```

### Add Index
```bash
# Generate migration
php artisan make:migration add_index_to_users_table

# Edit migration file
# database/migrations/YYYY_MM_DD_HHMMSS_add_index_to_users_table.php

# Run migration
php artisan migrate
```

## Troubleshooting

### Migration Fails
```bash
# Check migration status
php artisan migrate:status

# Check specific error
php artisan migrate --verbose

# Rollback and try again
php artisan migrate:rollback
php artisan migrate
```

### Database Connection Error
```bash
# Test database connection
php artisan db:show

# Check .env file
cat .env | grep DB_

# Verify credentials
php artisan tinker
>>> DB::connection()->getPdo()
```

### Table Already Exists
```bash
# Check if table exists
php artisan tinker
>>> Schema::hasTable('users')

# If exists, rollback migration
php artisan migrate:rollback

# Or manually drop table
php artisan tinker
>>> Schema::drop('users')
```

### Foreign Key Constraint Error
```bash
# Check foreign keys
php artisan tinker
>>> Schema::getForeignKeys('job_applications')

# Ensure referenced table exists
>>> Schema::hasTable('jobs_table')

# Check data integrity
>>> DB::table('job_applications')->whereNull('job_id')->count()
```

### SQLite vs MySQL Issues
```bash
# Check current database driver
php artisan tinker
>>> DB::connection()->getDriverName()

# Switch database in .env
DB_CONNECTION=mysql  # or sqlite

# Run migrations
php artisan migrate
```

## Backup & Restore

### Backup Database
```bash
# MySQL backup
mysqldump -h localhost -u root -p clinforce_api > backup.sql

# SQLite backup
cp database/database.sqlite database/database.sqlite.backup
```

### Restore Database
```bash
# MySQL restore
mysql -h localhost -u root -p clinforce_api < backup.sql

# SQLite restore
cp database/database.sqlite.backup database/database.sqlite
```

### Railway Backup
```bash
# Railway automatically backs up MySQL
# Access backups in Railway dashboard → MySQL service → Backups

# Manual backup via Railway
railway run mysqldump -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE > backup.sql
```

## Performance

### Add Indexes
```bash
# Generate migration
php artisan make:migration add_indexes_to_tables

# Add indexes in migration
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('created_at');
});

# Run migration
php artisan migrate
```

### Check Slow Queries
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog()
>>> // Run queries
>>> DB::getQueryLog()
```

### Optimize Database
```bash
# MySQL optimization
php artisan tinker
>>> DB::statement('OPTIMIZE TABLE users')
>>> DB::statement('ANALYZE TABLE users')
```

## Seeding

### Create Seeder
```bash
# Generate seeder
php artisan make:seeder UsersTableSeeder

# Edit seeder file
# database/seeders/UsersTableSeeder.php

# Run seeder
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UsersTableSeeder
```

### Seed with Migrations
```bash
# Fresh database with seeding
php artisan migrate:fresh --seed

# Refresh with seeding
php artisan migrate:refresh --seed
```

## Monitoring

### Check Database Size
```bash
# MySQL
SELECT table_schema, ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
GROUP BY table_schema;

# SQLite
SELECT page_count * page_size as size FROM pragma_page_count(), pragma_page_size();
```

### Check Table Sizes
```bash
# MySQL
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'clinforce_api'
ORDER BY size_mb DESC;
```

### Monitor Connections
```bash
# MySQL
SHOW PROCESSLIST;

# Kill connection
KILL process_id;
```

## Best Practices

1. **Always backup before major changes**
2. **Test migrations locally first**
3. **Use descriptive migration names**
4. **Keep migrations small and focused**
5. **Add proper indexes for performance**
6. **Use foreign keys for data integrity**
7. **Document complex migrations**
8. **Review migrations before deploying**
9. **Monitor database performance**
10. **Keep database clean and optimized**

## Resources

- [Laravel Migrations](https://laravel.com/docs/migrations)
- [Laravel Database](https://laravel.com/docs/database)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [SQLite Documentation](https://www.sqlite.org/docs.html)
