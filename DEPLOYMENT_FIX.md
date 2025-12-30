# Production Deployment Fix - shared-auth Package

## Problem
The error "shared-auth package not found" occurs because `shared-auth` is a symlink on your local machine, but symlinks don't work the same way on Hostinger.

## Solution: Upload the actual shared-auth directory

You have two options:

### Option 1: Copy from digital-id project (Recommended)

If you have the `digital-id` project on your local machine:

1. **Navigate to your digital-id directory:**
   ```bash
   cd /path/to/digital-id
   ```

2. **Create a zip file of the shared-auth directory:**
   ```bash
   zip -r shared-auth.zip shared-auth/
   ```

3. **Upload `shared-auth.zip` to your Hostinger server** in the `people-management-service` root directory (same level as `public/`, `config/`, etc.)

4. **Extract it via Hostinger File Manager or SSH:**
   - Via File Manager: Upload the zip, then extract it
   - Via SSH: `unzip shared-auth.zip` in the project root

5. **Verify the structure:**
   The directory structure should be:
   ```
   people-management-service/
   ├── shared-auth/
   │   ├── src/
   │   │   ├── Auth.php
   │   │   ├── Database.php
   │   │   ├── RBAC.php
   │   │   ├── CSRF.php
   │   │   ├── Email.php
   │   │   └── OrganisationalUnits.php
   │   ├── migrations/
   │   └── composer.json
   ├── public/
   ├── config/
   └── ...
   ```

### Option 2: Copy directly from local project

If you want to copy from your current local setup:

1. **Navigate to your people-management-service directory:**
   ```bash
   cd /Users/wellis/Desktop/Cursor/people-management-service
   ```

2. **Follow the symlink and create a real copy:**
   ```bash
   # Create a temporary copy
   cp -r shared-auth shared-auth-real
   
   # Or if the symlink resolves correctly:
   cp -rL shared-auth shared-auth-real
   ```

3. **Create a zip file:**
   ```bash
   zip -r shared-auth.zip shared-auth-real/
   ```

4. **Upload and extract on Hostinger** (same as Option 1, steps 3-5)

5. **Clean up local copy:**
   ```bash
   rm -rf shared-auth-real
   ```

## Verify the Fix

After uploading, visit:
- https://salmon-tarsier-739827.hostingersite.com/public/debug.php

This will show you if `shared-auth` is now found correctly.

## Important Notes

1. **Don't upload the symlink itself** - it won't work on the server
2. **Upload the actual directory contents** - all PHP files in `src/` are required
3. **File permissions** - Make sure PHP can read the files (usually 644 for files, 755 for directories)

## Quick Check Commands (if you have SSH access)

```bash
# Check if shared-auth exists
ls -la /path/to/people-management-service/shared-auth

# Check if required files exist
ls -la /path/to/people-management-service/shared-auth/src/

# Should show:
# Auth.php
# Database.php
# RBAC.php
# CSRF.php
# Email.php
# OrganisationalUnits.php
```

## After Fixing

Once `shared-auth` is uploaded, you should be able to access:
- https://salmon-tarsier-739827.hostingersite.com/public/
- https://salmon-tarsier-739827.hostingersite.com/public/login.php
- https://salmon-tarsier-739827.hostingersite.com/public/register.php

