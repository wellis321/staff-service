# Example Scripts

This directory contains example scripts demonstrating how to integrate with the Staff Service API.

## Scripts

### recruitment-system-example.php

Demonstrates how a recruitment system would send new hire data to the Staff Service to automatically create staff members.

**Usage**:
1. Edit the script and update the API key and URL
2. Run: `php examples/recruitment-system-example.php`

**What it does**:
- Sends a POST request to `/api/recruitment/import.php`
- Creates a new staff member with sample data
- Displays the response

### test-api.php

Simple script to test the Staff Service API endpoints.

**Usage**:
1. Make sure you're logged in via the web interface (session auth)
2. Or edit the script to use an API key
3. Run: `php examples/test-api.php`

**What it does**:
- Tests listing staff members
- Tests searching staff members
- Displays results

## Setting Up API Keys

Before using the API examples, you need to create an API key:

```bash
php scripts/create-api-key.php <user_id> <organisation_id> "Key Name"
```

Example:
```bash
php scripts/create-api-key.php 1 1 "Recruitment System"
```

The script will output your API key - save this securely.

## More Information

For complete API documentation, see [docs/API.md](../docs/API.md)

For getting started guide, see [docs/GETTING_STARTED.md](../docs/GETTING_STARTED.md)

