# Local Development Setup

This guide will help you set up the People Management Service for local development.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer (optional, for dependency management)
- Web server (Apache/Nginx) or PHP built-in server

## Step 1: Clone the Repository

```bash
git clone <repository-url> people-management-service
cd people-management-service
```

## Step 2: Set Up Shared Auth

The service requires the `shared-auth` package. If you have the Digital ID project in a sibling directory:

```bash
ln -sf ../digital-id/shared-auth shared-auth
```

Otherwise, copy or clone the shared-auth package:

```bash
# Copy from another location
cp -r /path/to/shared-auth .

# Or clone if it's a separate repository
git clone <shared-auth-repo-url> shared-auth
```

## Step 3: Create Environment File

Create a `.env` file in the project root:

```env
APP_ENV=development
APP_NAME=People Management
APP_URL=http://localhost:8000

DB_HOST=localhost
DB_NAME=people_management
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

CONTACT_EMAIL=admin@example.com

# Optional: Force /public/ prefix in URLs (for Hostinger configurations)
# Set to 1 if your URLs include /public/ even when document root is set to public/
FORCE_PUBLIC_PREFIX=0
```

## Step 4: Create Database

Create the database:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE people_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

## Step 5: Run Database Migrations

Run the complete schema (includes core auth tables):

```bash
mysql -u root -p people_management < sql/complete_schema.sql
```

Or if you already have the core auth tables from Digital ID:

```bash
mysql -u root -p people_management < sql/schema.sql
```

## Step 6: Set Up Uploads Directory

Create and set permissions for uploads:

```bash
mkdir -p uploads/people/photos/pending
chmod -R 755 uploads/
```

## Step 7: Start Development Server

### Option A: PHP Built-in Server

```bash
cd public
php -S localhost:8000
```

Then visit: http://localhost:8000

### Option B: Apache/Nginx

Configure your web server to point to the `public/` directory.

## Step 8: Create First User

1. Visit http://localhost:8000/register.php
2. Register with your email and organisation domain
3. Check your email for verification link
4. Verify your email
5. Log in

## Step 9: Create First Staff Member

1. Log in as an organisation administrator
2. Navigate to "Manage Staff"
3. Click "Add Staff Member"
4. Fill in the form and create a staff member

## Troubleshooting

### Database Connection Error

- Check your `.env` file has correct database credentials
- Verify MySQL is running
- Ensure database exists

### Shared Auth Not Found

- Verify the `shared-auth` symlink exists: `ls -la shared-auth`
- If symlink is broken, recreate it or copy the directory

### Permission Errors

- Ensure `uploads/` directory is writable: `chmod -R 755 uploads/`
- Check PHP has write permissions

### 403 Forbidden

- Ensure your web server document root points to `public/`
- Check `.htaccess` file exists in `public/` (for Apache)

### Photo Upload Issues

- Verify `uploads/people/photos/pending/` directory exists
- Check directory permissions are correct
- Verify PHP `upload_max_filesize` and `post_max_size` settings

## Development Tips

- Use browser developer tools to debug JavaScript issues
- Check PHP error logs for server-side errors
- Enable error display in development mode (already set in config.php for non-production)
- Use browser network tab to inspect API responses

## Next Steps

- Review the README.md for feature overview
- Check API documentation in `/api/staff-data.php`
- Explore the admin interface for staff management
- Test the self-service profile features

