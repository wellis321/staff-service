# Test Suite for People Management Service

This directory contains comprehensive tests for all forms in the People Management Service application.

## Setup

1. Install PHPUnit and dependencies:
```bash
composer install
```

2. Run all tests:
```bash
vendor/bin/phpunit
```

3. Run specific test suite:
```bash
vendor/bin/phpunit tests/Forms
```

4. Run specific test file:
```bash
vendor/bin/phpunit tests/Forms/LoginFormTest.php
```

## Test Coverage

### Forms Tested

1. **LoginFormTest** - Tests login form functionality
   - Form display
   - CSRF token validation
   - Email validation
   - Password validation
   - Invalid credentials handling
   - Rate limiting

2. **RegistrationFormTest** - Tests registration form functionality
   - Form display
   - CSRF token validation
   - Required field validation
   - Password matching validation
   - Password length validation
   - Rate limiting

3. **ContactFormTest** - Tests contact form functionality
   - Form display
   - CSRF token validation
   - Name validation
   - Email validation (format)
   - Subject validation
   - Message validation
   - Rate limiting

4. **StaffCreateFormTest** - Tests staff creation form
   - Form display
   - CSRF token validation
   - Required field validation
   - Optional field handling
   - Date field validation

5. **ProfileFormTest** - Tests profile form functionality
   - Form display
   - CSRF token validation
   - Email validation
   - File upload handling (photos)
   - Signature upload handling

6. **RequestAccessFormTest** - Tests organisation access request form
   - Form display
   - CSRF token validation
   - Required field validation
   - Email validation
   - Seats validation

## Test Structure

All tests extend `TestCase` which provides:
- Session management
- CSRF token generation
- POST/GET simulation
- Login/logout simulation
- Form validation helpers

## Running Tests

Tests can be run in several ways:

### Run all tests
```bash
vendor/bin/phpunit
```

### Run with coverage
```bash
vendor/bin/phpunit --coverage-html coverage/
```

### Run specific test
```bash
vendor/bin/phpunit tests/Forms/LoginFormTest.php
```

### Run with verbose output
```bash
vendor/bin/phpunit --verbose
```

## Adding New Tests

When adding new forms or functionality:

1. Create a new test file in `tests/Forms/`
2. Extend `TestCase`
3. Test all aspects:
   - Form display
   - CSRF protection
   - Field validation
   - Success scenarios
   - Error scenarios
   - Rate limiting (if applicable)
   - File uploads (if applicable)

## Notes

- Tests use output buffering to capture form output
- Tests simulate requests by setting `$_POST`, `$_GET`, `$_SERVER`
- Tests clean up after themselves in `tearDown()`
- Some tests may require database setup (create test users, organisations, etc.)


