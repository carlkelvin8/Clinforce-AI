# 🚀 Production Database Update Plan
## Adding New Features to Existing Production Database

### **Current Status:** ✅ Production database already deployed and running

---

## 📋 **STEP 1: ASSESSMENT**

### **Check Current Migration Status**
```bash
# SSH to production server
ssh user@your-production-server

# Navigate to project directory
cd /path/to/your/project

# Check which migrations have run
php artisan migrate:status

# See what new migrations are pending
php artisan migrate --pretend
```

### **Identify New Features to Add**
Based on our recent work, these are the NEW features that need to be added:
- ✅ **Portfolio Showcase** (portfolios table)
- ✅ **Skills Assessment** (assessment_templates, skills_assessments tables)
- ✅ **Candidate Analytics** (enhanced analytics)
- ✅ **Enhanced Job Features** (additional job fields)

---

## 🛡️ **STEP 2: SAFETY BACKUP**

### **Create Production Backup**
```bash
# Create timestamped backup
BACKUP_FILE="production_backup_$(date +%Y%m%d_%H%M%S).sql"

# For MySQL
mysqldump -u your_db_user -p your_database_name > $BACKUP_FILE

# For PostgreSQL  
pg_dump -U your_db_user -h localhost your_database_name > $BACKUP_FILE

# Compress backup
gzip $BACKUP_FILE

# Verify backup was created
ls -la production_backup_*.sql.gz
```

---

## 🔄 **STEP 3: INCREMENTAL MIGRATION STRATEGY**

### **Safe Migration Approach**
Since may existing data ka na, we'll use **ADDITIVE MIGRATIONS** only:

#### **What We'll Add (Safe Operations):**
```sql
-- ✅ SAFE: Create new tables
CREATE TABLE portfolios (...);
CREATE TABLE assessment_templates (...);
CREATE TABLE skills_assessments (...);

-- ✅ SAFE: Add new columns (nullable)
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL;
ALTER TABLE jobs_table ADD COLUMN additional_fields JSON NULL;

-- ✅ SAFE: Add new indexes
CREATE INDEX idx_portfolios_user_id ON portfolios(user_id);
CREATE INDEX idx_jobs_status ON jobs_table(status);
```

#### **What We WON'T Do (Risky Operations):**
```sql
-- ❌ RISKY: Don't modify existing columns
-- ALTER TABLE users MODIFY email VARCHAR(100); 

-- ❌ RISKY: Don't drop existing columns
-- ALTER TABLE jobs_table DROP COLUMN old_field;

-- ❌ RISKY: Don't rename existing tables
-- RENAME TABLE old_name TO new_name;
```

---

## 📝 **STEP 4: DEPLOYMENT SCRIPT**

### **Production Deployment Commands**
```bash
#!/bin/bash
# production-update.sh

echo "🚀 Starting production update..."

# 1. Backup database
echo "📦 Creating backup..."
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE
gzip $BACKUP_FILE
echo "✅ Backup created: $BACKUP_FILE.gz"

# 2. Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# 3. Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 4. Check what migrations will run
echo "🔍 Checking pending migrations..."
php artisan migrate --pretend

# 5. Put in maintenance mode (optional, for safety)
echo "🔧 Enabling maintenance mode..."
php artisan down --message="Adding new features - back in 2 minutes"

# 6. Run new migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# 7. Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 8. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Bring back online
echo "✅ Bringing application online..."
php artisan up

echo "🎉 Production update completed!"
echo "📦 Backup saved: $BACKUP_FILE.gz"
```

---

## 🧪 **STEP 5: TESTING STRATEGY**

### **Pre-Deployment Testing**
```bash
# 1. Test on local copy of production data
mysqldump -u prod_user -p prod_db > local_prod_copy.sql
mysql -u root -p local_test_db < local_prod_copy.sql

# 2. Run migrations locally
php artisan migrate

# 3. Test new features
# - Portfolio creation
# - Skills assessment
# - Analytics dashboard
# - All existing functionality
```

### **Post-Deployment Verification**
```bash
# 1. Check migration status
php artisan migrate:status

# 2. Verify database integrity
php artisan tinker
>>> User::count()
>>> Job::count()
>>> Portfolio::count()  // New table

# 3. Test API endpoints
curl -X GET https://yourdomain.com/api/health
curl -X GET https://yourdomain.com/api/portfolio

# 4. Check application logs
tail -f storage/logs/laravel.log
```

---

## 📊 **STEP 6: SPECIFIC MIGRATIONS FOR YOUR PROJECT**

### **New Tables to Add:**
```php
// portfolios table
Schema::create('portfolios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('type', ['image', 'video', 'link', 'document', 'project']);
    $table->string('media_url')->nullable();
    $table->json('tags')->nullable();
    $table->boolean('is_public')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->timestamps();
});

// assessment_templates table
Schema::create('assessment_templates', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->string('category');
    $table->json('questions');
    $table->integer('time_limit')->default(30);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// skills_assessments table  
Schema::create('skills_assessments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('template_id')->constrained('assessment_templates');
    $table->json('answers')->nullable();
    $table->integer('score')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

### **New Columns to Add:**
```php
// Add avatar to applicant_profiles (if not exists)
Schema::table('applicant_profiles', function (Blueprint $table) {
    if (!Schema::hasColumn('applicant_profiles', 'avatar')) {
        $table->string('avatar')->nullable()->after('bio');
    }
});

// Add portfolio fields to users (if needed)
Schema::table('users', function (Blueprint $table) {
    if (!Schema::hasColumn('users', 'portfolio_public')) {
        $table->boolean('portfolio_public')->default(false);
    }
});
```

---

## 🚨 **ROLLBACK PLAN**

### **If Something Goes Wrong:**
```bash
# 1. Put in maintenance mode
php artisan down

# 2. Rollback migrations
php artisan migrate:rollback --step=5  # Rollback last 5 migrations

# 3. Or restore from backup (nuclear option)
mysql -u $DB_USER -p$DB_PASS $DB_NAME < backup_file.sql

# 4. Revert code
git checkout previous_working_commit
composer install --no-dev

# 5. Clear caches and bring back online
php artisan optimize:clear
php artisan up
```

---

## ✅ **STEP 7: EXECUTION CHECKLIST**

### **Pre-Deployment:**
- [ ] **Production backup created and verified**
- [ ] **Migrations tested on local copy of production data**
- [ ] **All team members notified**
- [ ] **Rollback plan prepared**

### **During Deployment:**
- [ ] **Maintenance mode enabled (optional)**
- [ ] **Latest code pulled**
- [ ] **Dependencies updated**
- [ ] **Migrations run successfully**
- [ ] **Caches cleared and optimized**
- [ ] **Application brought back online**

### **Post-Deployment:**
- [ ] **Migration status verified**
- [ ] **New features tested**
- [ ] **Existing functionality verified**
- [ ] **No errors in logs**
- [ ] **Performance acceptable**

---

## 🎯 **RECOMMENDED EXECUTION**

### **Safest Approach:**
```bash
# 1. During low-traffic hours (early morning/late night)
# 2. Have team member available for support
# 3. Monitor closely for first hour after deployment

# Quick deployment (if confident):
php artisan down --message="Adding new features"
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan up

# Monitor logs
tail -f storage/logs/laravel.log
```

---

## 💡 **KEY POINTS**

1. **Your existing data is SAFE** - we're only adding new tables/columns
2. **Backup first** - always have a rollback option
3. **Test locally** - use copy of production data
4. **Monitor closely** - watch for any issues after deployment
5. **Incremental approach** - add features gradually if needed

**Bottom line:** Since naka-deploy na yung base system mo, ang gagawin natin ay **additive updates** lang - safe and non-destructive! 🚀