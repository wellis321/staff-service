# People Management Service

A standalone People Management Service (PMS) that serves as the **single source of truth** for staff and employee data across all applications. Built with PHP and MySQL, deployable separately on Hostinger, using the same `shared-auth` package as Digital ID.

## Overview

The People Management Service is designed to be the **central hub** for staff data, feeding information to other applications (Digital ID, Medication App, etc.) and external systems (Entra/Microsoft 365, HR systems, Finance systems, LMS systems). While each application can operate **standalone**, when Staff Service is present, it becomes the preferred source for staff information.

The People Management Service provides:

- **Staff/Employee Management**: Complete staff profile management with self-service capabilities
- **Organisational Unit Assignments**: Link staff to organisational units/teams using the shared-auth organisational structure
- **Photo Management**: Photo upload and approval workflow
- **Multi-tenant Support**: Full organisation-level data isolation
- **API Access**: RESTful API for other applications and external systems to access staff data
- **Export Functionality**: CSV and JSON export of staff data
- **Webhook Support**: Real-time notifications for staff data changes
- **External System Integration**: Single point for Entra, HR, Finance, LMS integration
- **Recruitment System Import**: Receive new hire data from recruitment systems

## Features

### Staff Self-Service

- View own profile
- Update contact information (phone, email)
- Update emergency contact details
- Upload/update profile photo (subject to admin approval)
- View organisational unit assignments

### Admin Staff Management

- List all staff (with filters: active/inactive, organisational unit, search)
- Create new staff member
- Link staff to existing user account or create without account link
- Edit staff profile (all fields)
- Assign staff to organisational units
- Set line manager relationships
- Deactivate/reactivate staff
- Export staff data (CSV/JSON)
- Approve/reject staff photos

### Organisational Unit Integration

- Uses shared-auth's organisational units system
- Assign staff to multiple units
- Designate primary unit
- View staff by organisational unit
- Unit-level admin permissions (using shared-auth RBAC)

### API/Integration Endpoints

- RESTful API for other apps and external systems to read staff data
- Support filtering by organisation_id (mandatory for security)
- Pagination and search capabilities
- JSON responses
- API key authentication (for external systems)
- Session-based authentication (for same-domain apps)
- Webhook subscriptions for real-time updates

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Authentication**: Shared `shared-auth` package (same as Digital ID)
- **Deployment**: Hostinger (separate from Digital ID)
- **Codebase**: Separate repository/codebase

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Access to shared-auth package (can be symlinked from Digital ID project)
- Web server (Apache/Nginx)

## Quick Start

### Starting the Application

The easiest way to start the application for local development is using the included start script:

```bash
cd people-management-service
./start.sh
```

This will start the PHP built-in development server on **http://localhost:8000**

Alternatively, you can start the server manually:

```bash
cd people-management-service/public
php -S localhost:8000
```

Then open your browser and visit: **http://localhost:8000**

> **Note:** Make sure you've completed the installation steps below (database setup, .env configuration, etc.) before starting the application.

### Getting Started Guide

For a step-by-step guide on using the Staff Service, including how to integrate with recruitment systems, see:

- **[Getting Started Guide](docs/GETTING_STARTED.md)** - Complete getting started guide
- **[Quick Start](docs/QUICK_START.md)** - 5-minute quick start for recruitment integration

## Installation

### 1. Clone/Copy the Project

```bash
git clone <repository-url> people-management-service
cd people-management-service
```

### 2. Set Up Shared Auth

The service requires the `shared-auth` package. You can either:

**Option A: Symlink (if sibling to digital-id project)**
```bash
ln -sf ../digital-id/shared-auth shared-auth
```

**Option B: Copy the shared-auth directory**
```bash
cp -r /path/to/digital-id/shared-auth .
```

### 3. Configure Environment

Create a `.env` file in the project root:

```env
APP_ENV=development
APP_NAME=People Management
APP_URL=http://localhost

DB_HOST=localhost
DB_NAME=digital_id
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

CONTACT_EMAIL=your-email@example.com

# Optional: Force /public/ prefix in URLs (for Hostinger configurations)
# Set to 1 if your URLs include /public/ even when document root is set to public/
FORCE_PUBLIC_PREFIX=0
```

### 4. Set Up Database

Run the database schema creation:

**Option A: Complete schema (if starting fresh)**
```bash
mysql -u root -p < sql/complete_schema.sql
```

**Option B: Schema only (if core auth tables already exist)**
```bash
mysql -u root -p < sql/schema.sql
```

### 5. Start the Application

**For Local Development:**

Use the included start script:
```bash
./start.sh
```

Or manually start the PHP built-in server:
```bash
cd public
php -S localhost:8000
```

Visit http://localhost:8000 in your browser.

**For Production (Hostinger):**

Point your web server's document root to the `public/` directory.

**Apache Example:**
```apache
<VirtualHost *:80>
    ServerName pms.local
    DocumentRoot /path/to/people-management-service/public
    
    <Directory /path/to/people-management-service/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx Example:**
```nginx
server {
    listen 80;
    server_name pms.local;
    root /path/to/people-management-service/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 6. Set Permissions

Ensure uploads directory is writable:

```bash
chmod -R 755 uploads/
```

## Usage

### Staff Self-Service

1. Log in to the system
2. Navigate to "My Profile"
3. View and update your profile information
4. Upload a profile photo (requires admin approval)

### Admin Staff Management

1. Log in as an organisation administrator
2. Navigate to "Manage Staff"
3. Create, edit, or view staff members
4. Assign staff to organisational units
5. Approve/reject staff photos
6. Export staff data

### API Usage

Access staff data via the API endpoint:

```
GET /api/staff-data.php
GET /api/staff-data.php?id=123
GET /api/staff-data.php?search=john
GET /api/staff-data.php?include_inactive=1
```

Returns JSON data with staff information and organisational unit assignments.

### Recruitment System Integration

Automatically create staff members from recruitment systems:

```
POST /api/recruitment/import.php
Authorization: Bearer <api-key>
Content-Type: application/json

{
  "source_system": "recruitment-system-v1",
  "new_hire": {
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane.smith@example.com",
    "employee_reference": "EMP2024001",
    "job_title": "Support Worker",
    "employment_start_date": "2024-02-01",
    "organisational_unit": "Care Team A"
  }
}
```

See [Getting Started Guide](docs/GETTING_STARTED.md) for complete examples and setup instructions.

### Export Staff Data

Export staff data in CSV or JSON format:

```
GET /api/export-staff.php?format=csv
GET /api/export-staff.php?format=json
```

## Database Schema

The service uses the following core tables:

- **people**: Unified table for all person types (staff, people we support)
- **staff_profiles**: Staff-specific data (job title, line manager, etc.)
- **person_organisational_units**: Many-to-many relationship linking people to organisational units
- **api_keys**: API keys for external system authentication
- **webhook_subscriptions**: Webhook subscriptions for real-time updates
- **external_system_sync**: Tracking for external system synchronisation
- **recruitment_imports**: Log of imports from recruitment systems

For full schema details, see `sql/schema.sql` or `sql/complete_schema.sql`.

## Security

- Multi-tenant isolation (organisation-level)
- Role-based access control (using shared-auth RBAC)
- Staff can only edit their own profile (with admin override)
- Admins can manage all staff in their organisation
- API endpoints require authentication
- All queries filtered by organisation_id

## Integration with Other Applications

The Staff Service is designed to integrate with other applications and external systems. See the comprehensive documentation:

- **[Integration Guide](docs/INTEGRATION.md)** - How other apps can integrate with Staff Service
- **[External Systems](docs/EXTERNAL_SYSTEMS.md)** - Integration with Entra, HR, Finance, LMS systems
- **[API Reference](docs/API.md)** - Complete API documentation for developers

### Key Integration Points

- **Digital ID**: Can read staff data via API, syncs name, photo, employee reference
- **Medication App** (Future): Will query staff data for medication administration
- **External Systems**: Entra/Microsoft 365, HR systems, Finance systems, LMS systems
- **Recruitment Systems**: Receive new hire data via API

## Development Standards

- UK English spelling throughout
- Font Awesome 6 icons (no emojis)
- Same coding patterns and structure as Digital ID
- Same security practices (CSRF, SQL injection prevention, etc.)

## File Structure

```
people-management-service/
├── config/
│   ├── config.php
│   ├── database.php
│   └── env_loader.php
├── shared-auth/          # Symlink or copy of shared-auth
├── includes/
│   ├── header.php
│   └── footer.php
├── public/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── profile.php          # Staff view/edit own profile
│   ├── view-image.php       # View uploaded photos
│   ├── staff/
│   │   ├── index.php        # Admin: List all staff
│   │   ├── view.php         # Admin: View staff profile
│   │   ├── edit.php         # Admin: Edit staff profile
│   │   └── create.php       # Admin: Create new staff member
│   └── api/
│       ├── staff-data.php   # API endpoint for other apps
│       └── export-staff.php # Export functionality
├── src/
│   └── models/
│       └── Person.php       # Model for people table
├── sql/
│   ├── schema.sql
│   └── complete_schema.sql
└── uploads/
    └── people/
        └── photos/
```

## Architecture & Integration

The Staff Service is designed with a flexible architecture that supports both standalone operation and integration with other applications.

### Standalone Operation

- Each app can function independently without Staff Service
- Apps maintain their own authentication (using shared-auth)
- No hard dependencies between apps

### Integrated Operation (Preferred)

- When Staff Service exists, other apps prefer it as the source of truth
- Integration via REST API (JSON)
- Apps can cache staff data locally but sync periodically
- Staff Service provides webhooks for real-time updates

### Central Hub Pattern

Staff Service sits at the center of the ecosystem, feeding data to:
- **Applications**: Digital ID, Medication App (future), other apps
- **External Systems**: Entra/Microsoft 365, HR systems, Finance systems, LMS systems

And receiving data from:
- **Recruitment System**: Initial staff data when new hires are confirmed

### Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[Architecture](docs/ARCHITECTURE.md)** - Architecture overview and design decisions
- **[API Reference](docs/API.md)** - Complete API documentation
- **[Integration Guide](docs/INTEGRATION.md)** - How to integrate with Staff Service
- **[External Systems](docs/EXTERNAL_SYSTEMS.md)** - External system integration patterns

## Integration with Other Applications

### Digital ID Integration

When Digital ID is configured to use Staff Service:
- Digital ID reads staff data from Staff Service API
- Digital ID maintains references to Staff Service person IDs
- Digital ID syncs staff data periodically or on-demand
- Digital ID continues to manage: ID card data, verification logs, check-ins

### Medication App Integration (Future)

The Medication App will:
- Query Staff Service for staff who can administer medication
- Sync staff information: name, photo, qualifications, organisational units
- Maintain medication-specific data: medication records, administration logs

### External System Integration

Staff Service provides APIs and webhooks for:
- **Microsoft Entra/365**: User account synchronisation
- **HR Systems**: Employment data for payroll processing
- **Finance Systems**: Staff data for payroll and cost centre allocation
- **LMS Systems**: Staff list for course assignments

See [External Systems Documentation](docs/EXTERNAL_SYSTEMS.md) for details.

## License

[Your License Here]

## Support

For support, email: [CONTACT_EMAIL from .env]

