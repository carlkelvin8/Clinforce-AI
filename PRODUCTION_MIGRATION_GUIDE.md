# 🚀 Production Migration Guide
## Safe Database Migration Strategy

### **🎯 Goal:** Add new tables/fields without losing existing production data

---

## 📋 **PRE-MIGRATION CHECKLIST**

### **1. Database Backup (CRITICAL)**
```bash
# Create full database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Or for PostgreSQL
pg_dump -U username -h hostname database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup file
ls -la backup_*.sql
```

### **2. Test Migration Locally**
```bash
# Copy production database to local
mysql -u root -p local_db < production_backup.sql

# Run migrations locally first
php artisan migrate --pretend  # See what will run
php artisan migrate            # Actually run migrations

# Test application functionality
```

### **3. Check Migration Status**
```bash
# See which migrations have run in production
php artisan migrate:status

# Check for any pending migrations
php artisan migrate --pretend
```

---

## 🔄 **MIGRATION STRATEGIES**

### **Strategy 1: Standard Migration (Recommended)**
```bash
# 1. Backup database
mysqldump -u username -p database_name > backup_before_migration.sql

# 2. Put application in maintenance mode
php artisan down --message="Database migration in progress"

# 3. Run migrations
php artisan migrate --force

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Bring application back up
php artisan up
```

### **Strategy 2: Zero-Downtime Migration**
For large databases where downtime must be minimized:

```bash
# 1. Create new columns as nullable first
php artisan make:migration add_new_fields_nullable

# 2. Deploy code that handles both old and new schema
# 3. Run migration to add nullable columns
php artisan migrate

# 4. Populate new columns with data
php artisan make:command PopulateNewFields
php artisan populate:new-fields

# 5. Make columns non-nullable if needed
php artisan make:migration make_new_fields_required
```

---

## 📝 **SAFE MIGRATION PRACTICES**

### **1. Write Reversible Migrations**
```php
// Good migration example
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone')->nullable()->after('email');
        $table->timestamp('last_login_at')->nullable();
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['phone', 'last_login_at']);
    });
}
```

### **2. Use Transactions for Data Migrations**
```php
public function up()
{
    DB::transaction(function () {
        // Create new table
        Schema::create('new_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        // Migrate existing data
        DB::table('old_table')->chunk(1000, function ($records) {
            foreach ($records as $record) {
                DB::table('new_table')->insert([
                    'name' => $record->old_name,
                    'created_at' => $record->created_at,
                    'updated_at' => now(),
                ]);
            }
        });
    });
}
```

### **3. Add Indexes After Data Population**
```php
public function up()
{
    // 1. Create table
    Schema::create('large_table', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->timestamps();
    });
    
    // 2. Add index separately (faster for large tables)
    Schema::table('large_table', function (Blueprint $table) {
        $table->index('email');
    });
}
```

---

## 🛡️ **PRODUCTION DEPLOYMENT STEPS**

### **Step 1: Preparation**
```bash
# 1. Test everything locally
php artisan migrate:fresh --seed  # Test with fresh DB
php artisan migrate               # Test incremental migration

# 2. Check for migration conflicts
git log --oneline database/migrations/

# 3. Verify all team migrations are included
php artisan migrate:status
```

### **Step 2: Production Backup**
```bash
# Create timestamped backup
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE

# Compress backup
gzip $BACKUP_FILE

# Store backup safely
cp $BACKUP_FILE.gz /path/to/backups/
```

### **Step 3: Deploy & Migrate**
```bash
# 1. Pull latest code
git pull origin main

# 2. Install/update dependencies
composer install --no-dev --optimize-autoloader

# 3. Put in maintenance mode
php artisan down --message="Updating database schema"

# 4. Run migrations
php artisan migrate --force

# 5. Clear all caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Bring back online
php artisan up
```

### **Step 4: Verification**
```bash
# Check migration status
php artisan migrate:status

# Verify application health
curl -I https://yourdomain.com/api/health

# Check logs for errors
tail -f storage/logs/laravel.log
```

---

## 🚨 **ROLLBACK STRATEGY**

### **If Migration Fails:**
```bash
# 1. Put in maintenance mode
php artisan down

# 2. Rollback last migration batch
php artisan migrate:rollback

# 3. Or rollback specific migration
php artisan migrate:rollback --step=1

# 4. Or restore from backup (last resort)
mysql -u username -p database_name < backup_file.sql

# 5. Bring back online
php artisan up
```

### **Emergency Rollback:**
```bash
# Complete database restore
mysql -u $DB_USER -p$DB_PASS $DB_NAME < backup_before_migration.sql

# Revert code to previous version
git checkout previous_commit_hash
composer install --no-dev
php artisan optimize:clear
php artisan up
```

---

## 📊 **MONITORING & VALIDATION**

### **Post-Migration Checks:**
```bash
# 1. Database integrity
php artisan tinker
>>> DB::select('SELECT COUNT(*) FROM users');
>>> User::count();

# 2. Application functionality
curl -X POST https://yourdomain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# 3. Check for errors
tail -f storage/logs/laravel.log

# 4. Monitor performance
# Check slow query log
# Monitor memory usage
# Check response times
```

---

## 🔧 **SPECIFIC COMMANDS FOR YOUR PROJECT**

### **For Your Healthcare Platform:**
```bash
# 1. Backup production database
mysqldump -u clinforce_user -p clinforce_db > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Check current migration status
php artisan migrate:status

# 3. See what migrations will run
php artisan migrate --pretend

# 4. Run migrations (with maintenance mode)
php artisan down --message="Adding new features - back in 5 minutes"
php artisan migrate --force
php artisan optimize:clear
php artisan up

# 5. Verify new features work
# Test portfolio creation
# Test skills assessment
# Test analytics dashboard
```

---

## 📋 **MIGRATION CHECKLIST**

### **Before Migration:**
- [ ] **Full database backup created**
- [ ] **Migrations tested locally**
- [ ] **Team notified of maintenance window**
- [ ] **Rollback plan prepared**
- [ ] **Monitoring tools ready**

### **During Migration:**
- [ ] **Maintenance mode enabled**
- [ ] **Migrations run successfully**
- [ ] **No errors in logs**
- [ ] **Caches cleared**
- [ ] **Application brought back online**

### **After Migration:**
- [ ] **All features working**
- [ ] **Database integrity verified**
- [ ] **Performance acceptable**
- [ ] **No user complaints**
- [ ] **Backup cleanup scheduled**

---

## 🎯 **BEST PRACTICES**

### **1. Timing:**
- **Low traffic hours** (early morning/late night)
- **Avoid Fridays** (in case issues need fixing)
- **Coordinate with team** (ensure support available)

### **2. Communication:**
- **Notify users** of maintenance window
- **Update status page** if available
- **Have support team ready**

### **3. Testing:**
- **Test on staging** environment first
- **Use production data copy** for testing
- **Verify all features** work after migration

### **4. Monitoring:**
- **Watch error logs** closely
- **Monitor performance** metrics
- **Check user feedback** channels

---

## 🚀 **AUTOMATED DEPLOYMENT SCRIPT**

```bash
#!/bin/bash
# production-deploy.sh

set -e  # Exit on any error

echo "🚀 Starting production deployment..."

# Configuration
BACKUP_DIR="/backups"
DB_NAME="clinforce_db"
DB_USER="clinforce_user"

# 1. Create backup
echo "📦 Creating database backup..."
BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE
gzip $BACKUP_FILE

# 2. Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# 3. Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 4. Maintenance mode
echo "🔧 Enabling maintenance mode..."
php artisan down --message="Database migration in progress"

# 5. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 6. Clear caches
echo "🧹 Clearing caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Bring back online
echo "✅ Bringing application back online..."
php artisan up

echo "🎉 Deployment completed successfully!"
echo "📦 Backup saved to: $BACKUP_FILE.gz"
```

---

## 💡 **KEY TAKEAWAYS**

1. **Always backup first** - No exceptions!
2. **Test locally** before production
3. **Use maintenance mode** for safety
4. **Have rollback plan** ready
5. **Monitor after deployment**
6. **Communicate with users**

**Remember:** Ang production data mo ay **irreplaceable**. Better to be overly cautious than lose important data! 🛡️