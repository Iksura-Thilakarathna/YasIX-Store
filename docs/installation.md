# YasIX Store — Local Installation & Database Initialization Guide

This guide provides step-by-step instructions to set up, configure, and initialize the **YasIX Store** application in a local development environment using Windows and XAMPP (or standalone PHP 8.x / MySQL 8.x).

---

## 📋 System Prerequisites

Ensure your system meets the following specifications before installing:

| Component | Minimum Required | Recommended | Tested Verification |
|---|---|---|---|
| **Operating System** | Windows 10, Linux (Ubuntu 20.04+), macOS 12+ | Windows 11 (64-bit) | Windows 11 / PowerShell |
| **PHP Runtime** | PHP 8.1.0+ | PHP 8.2+ or 8.3 | PHP 8.2.12 (CLI / Apache Module) |
| **Database Server** | MySQL 8.0+ or MariaDB 10.4+ | MySQL 8.0+ | MariaDB 10.4.32 / InnoDB |
| **Web Server** | Apache 2.4+ with `mod_rewrite` | Apache 2.4 (XAMPP) | Apache 2.4.58 / PHP Built-in Server |

### Required PHP Extensions
Verify that the following extensions are enabled in your `php.ini`:
- `pdo` & `pdo_mysql` (Relational database communication via prepared statements)
- `mbstring` (Multibyte UTF-8 string processing)
- `openssl` (Cryptographically secure random token generation)
- `session` (Secure server-side user and admin session management)
- `fileinfo` (Strict MIME-type validation for image uploads)
- `json` (JSON payload encode / decode)

To verify active extensions in your command line:
```bash
php -m | findstr -i "pdo pdo_mysql mbstring openssl session fileinfo"
```

---

## 🛠️ Windows & XAMPP Setup Guide

### 1. Repository Placement
Clone or move the repository into your local web root. If using standard XAMPP on Windows:

```powershell
Set-Location -Path "C:\xampp\htdocs"
git clone https://github.com/Iksura-Thilakarathna/YasIX-Store.git
Set-Location -Path "YasIX-Store"
```

The application root directory structure:
```
C:\xampp\htdocs\YasIX-Store\
├── app/
├── config/
├── database/
├── docs/
├── public/          <-- Web server document root
├── tests/
└── .env.example
```

---

## ⚙️ Environment Configuration

### 1. Create `.env` from Template
Copy the provided environment template to a new file named `.env`:

```powershell
Copy-Item -Path ".env.example" -Destination ".env"
```

### 2. Configure Environment Parameters
Open `.env` in your text editor and adjust settings to match your local setup:

```ini
# Application Mode
APP_NAME="YasIX Store"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Relational Database Connection (Default XAMPP settings)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=yasix_store
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Session & Cryptographic Token Configuration
SESSION_LIFETIME=7200
CSRF_SECRET=cyber_yasix_secret_key_change_in_production
```

> [!IMPORTANT]
> The `.env` file contains localized secrets and is explicitly ignored by Git (`.gitignore`). Never commit production credentials, API keys, or live passwords to version control.

---

## 🗄️ Database Initialization & Migration Sequence

The database setup follows a 3-stage process: base schema creation, automated schema evolution, and baseline data seeding.

### Step 1: Start MySQL Server
Ensure the MySQL daemon is running:
- **Via XAMPP Control Panel**: Click **Start** next to MySQL.
- **Via PowerShell / Command Line**:
  ```powershell
  Set-Location -Path "C:\xampp"; & "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=mysql\bin\my.ini --standalone
  ```

### Step 2: Create Database & Import Base Relational Schema
Execute the following commands to create the `yasix_store` database and apply `database/schema.sql`:

```powershell
# Create database with UTF-8 Multibyte character set
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS yasix_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import baseline 18-table relational schema
& "C:\xampp\mysql\bin\mysql.exe" -u root yasix_store < database/schema.sql
```

### Step 3: Run Database Migrations
Apply schema evolutions (order tracking lifecycle, COD payment states, inventory audit logs, and warranty claim tables):

```powershell
php database/migrate.php
```

Expected output:
```text
Applying migration: 2026_10_13_day5_order_warranty.sql... Applied successfully.
Applying migration: 2026_10_16_day6_admin_management.sql... Applied successfully.
All database migrations applied cleanly.
```

### Step 4: Import Baseline Seed Data
Import categories, initial product catalog items, and regional volumetric delivery rules:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root yasix_store < database/seed.sql
```

*Note: The seed script uses `INSERT INTO ...` with explicit primary IDs. If re-running, clean existing tables first to avoid unique key collisions.*

---

## 🛡️ Safe Administrator Provisioning

To prevent unauthorized administrative access, public customer registration (`/register`) **only** provisions accounts with the `CUSTOMER` role. 

Administrator accounts cannot be registered through web forms. Use the CLI provisioning script:

```powershell
php scripts/provision_admin.php "System Administrator" "admin@yasix.com" "AdminPass123!"
```

Parameters:
1. **Full Name**: e.g., `"System Administrator"`
2. **Email Address**: e.g., `"admin@yasix.com"`
3. **Password**: Must be at least 8 characters. The script automatically hashes the password using `PASSWORD_BCRYPT`.

### Seed Test Accounts (Development Only)
When initial seeds are applied, the following test credentials are created:

| Role | Email | Password | Access Scope |
|---|---|---|---|
| **Administrator** | `admin@yasix.com` | `AdminPass123!` | `/admin` Command Hub, Orders, Products, Inventory, Reports |
| **Customer** | `customer@yasix.local` | `CustomerPass123!` | Storefront, Cart, Checkout, `/orders`, `/warranties` |

---

## 🚀 Running the Web Application Locally

You can run YasIX Store using either PHP's built-in web server or Apache in XAMPP:

### Option A: Built-in PHP Development Server (Quickest)
From the project root directory, launch PHP's internal server pointing to `public/`:

```powershell
php -S localhost:8000 -t public
```

Open your browser and navigate to:
- **Storefront**: [http://localhost:8000](http://localhost:8000)
- **Customer Login**: [http://localhost:8000/login](http://localhost:8000/login)
- **Admin Portal**: [http://localhost:8000/admin](http://localhost:8000/admin)

### Option B: Apache / XAMPP VirtualHost
For complete Apache hosting, configure a VirtualHost in `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName yasix.local
    DocumentRoot "C:/xampp/htdocs/YasIX-Store/public"
    <Directory "C:/xampp/htdocs/YasIX-Store/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add the domain to your `C:\Windows\System32\drivers\etc\hosts` file:
```text
127.0.0.1   yasix.local
```
Restart Apache and navigate to `http://yasix.local`.

---

## 🧪 Verification & Running Test Suites

Verify your local installation by running the full automated verification suite:

```powershell
# Run the complete Day 9 release readiness verification
php tests/verify_release_readiness.php

# Run front controller route and template dispatch audit
php tests/test_dispatch.php

# Run full end-to-end integration test
php tests/verify_day7_complete.php
```

All tests execute in the local CLI environment with zero external dependencies and report `[PASS]` on all checkpoints.

---

## 🔧 Troubleshooting Common Setup Issues

### 1. `SQLSTATE[HY000] [2002] Connection refused`
- **Cause**: MySQL daemon is not running on host `127.0.0.1` port `3306`.
- **Resolution**:
  1. Open the XAMPP Control Panel and start the MySQL service.
  2. Verify port binding in PowerShell: `netstat -ano | findstr 3306`.
  3. Ensure `DB_HOST=127.0.0.1` in your `.env` rather than `localhost` to avoid socket resolution issues on Windows.

### 2. `404 Not Found` on Routes Other than `/` (e.g. `/products`, `/login`)
- **Cause**: Apache `mod_rewrite` is not processing `.htaccess` rewrite rules.
- **Resolution**:
  1. Confirm that `public/.htaccess` exists in your repository.
  2. In `C:\xampp\apache\conf\httpd.conf`, verify `LoadModule rewrite_module modules/mod_rewrite.so` is uncommented.
  3. Ensure `AllowOverride All` is set for your web root directory.

### 3. `Call to undefined function imagecreatefromjpeg()` or Missing Upload Extensions
- **Cause**: PHP GD or Fileinfo extension is disabled.
- **Resolution**:
  1. Open `C:\xampp\php\php.ini`.
  2. Uncomment `extension=fileinfo` and `extension=gd`.
  3. Restart Apache.

### 4. Upload Directory Permission Error
- **Cause**: The application cannot write uploaded product images to `public/assets/images/products`.
- **Resolution**:
  1. Ensure the directory exists: `New-Item -ItemType Directory -Force -Path "public/assets/images/products"`.
  2. On Windows, verify write permissions for your local user account. On Linux/macOS: `chmod -R 775 public/assets/images`.
