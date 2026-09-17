# Quick Start Guide — SimpleInventorySystem-PHP

## 🚀 Setup

### 1. Configure

```bash
cp config/app_simple.example.php config/app_simple.php
cp php_action/db_connect.example.php php_action/db_connect.php
```

Edit `config/app_simple.php` with your real database and mail credentials.
`php_action/db_connect.php` delegates to that file so the installation has one
database source of truth. Then generate a real encryption key in
`config/app_simple.php`:

```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

```php
define('ENCRYPTION_KEY', 'paste-the-generated-value-here');
```

### 2. Database

`DATABASE FILE/sinventoryphp_complete.sql` is the only supported installation
base. Do **not** use `DATABASE FILE/sinventoryphp.sql`: it is a retired 2022
legacy snapshot that does not contain the customer, activity-log, or current
product schema required by the application.

The PHP data migrations must run at their numbered dependency points; a
`migrations/*.sql`-only loop is incomplete and will leave tracked inventory and
staff permissions unusable.

```bash
mysql -u root -e "CREATE DATABASE sinventoryphp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root sinventoryphp < "DATABASE FILE/sinventoryphp_complete.sql"

# Schema migrations through the inventory-core schema.
for f in \
  migrations/001_create_password_reset_tokens.sql \
  migrations/002_create_transactions_tables.sql \
  migrations/003_create_enhancement_tables.sql \
  migrations/004_add_user_roles.sql \
  migrations/005_add_sack_kilo_conversion.sql \
  migrations/006_widen_legacy_unit_columns.sql \
  migrations/007_widen_product_sold.sql \
  migrations/008_add_login_rate_limiting.sql \
  migrations/009_add_missing_indexes.sql \
  migrations/010_inventory_core_schema.sql
do
  mysql -u root sinventoryphp < "$f"
done

# Data migration 011 depends on schema 010 and the inventory service.
php migrations/011_create_legacy_batches.php

mysql -u root sinventoryphp < migrations/012_add_custom_permissions.sql
php migrations/013_grant_default_sales_permissions.php

for f in \
  migrations/014_add_product_identifiers.sql \
  migrations/015_add_payment_method.sql \
  migrations/016_create_sales_returns.sql
do
  mysql -u root sinventoryphp < "$f"
done

php migrations/017_seed_settings_and_returns_permission.php

# There is intentionally no 018 file. transactions.or_number is already
# UNIQUE in both the supported base dump and migration 002.
mysql -u root sinventoryphp < migrations/019_add_orders_user_id_index.sql
php migrations/020_grant_default_inventory_permissions.php

for f in \
  migrations/021_add_performance_indexes.sql \
  migrations/022_orders_money_to_decimal.sql \
  migrations/023_normalize_product_prices_and_api_tokens.sql \
  migrations/024_create_notification_reads.sql \
  migrations/025_enforce_product_identifier_uniqueness.sql \
  migrations/026_reconcile_seeded_inventory_ledger.sql
do
  mysql -u root sinventoryphp < "$f"
done
```

The complete dump already contains demo data, so do not run
`migrations/seed_agrivet_data.php` during normal installation. If you
deliberately run that optional seeder on a custom empty database, run it only
after all schema migrations, then rerun the idempotent
`migrations/011_create_legacy_batches.php` so every tracked product is fully
represented by batches.

### 3. Login

- URL: `http://localhost/SimpleInventorySystem-PHP/login_secure.php`
- Log in with your seeded admin account, then immediately change the password via
  Profile → Change Password.

---

## 📁 Key Files

```
SimpleInventorySystem-PHP/
├── config/app_simple.php        # Real config (gitignored — copy from .example.php)
├── php_action/db_connect.php    # Real DB connection (gitignored — copy from .example.php)
├── php_action/                  # CRUD handlers, guarded by core.php + csrf_guard.php
│                                 #   (+ rbac_guard.php for admin-only actions)
├── migrations/                  # Numbered schema migrations, applied in order
├── includes/header_sidebar.php  # Shared nav/session gate for most pages
├── bootstrap_simple.php         # Auth (SimpleAuth/SimpleSecurity) — the live auth path
├── login_secure.php             # Login page
└── dashboard_secure.php         # Main dashboard
```

## 🔐 Roles

Two roles exist (`users.role` column): `admin` and `staff`. Admin-only pages
(`user.php`, `setting.php`, `report.php`, `stock_forecast.php`,
`inventory_valuation.php`, `activity_logs.php`) call `SimpleSecurity::requireAdmin()`
server-side — non-admins are redirected, not just hidden from the nav.

## 🐛 Troubleshooting

- **DB connection error**: check `php_action/db_connect.php` and
  `config/app_simple.php` credentials, and that MySQL/MariaDB is running.
- **"Too many redirects"**: ensure `logs/` is writable and clear cookies.
- **Permission errors**: `chmod 755 logs`.
