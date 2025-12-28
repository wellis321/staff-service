# Testing Guide for People Management Service

This document explains how to run and maintain the test suite for the People Management Service.

## Quick Start

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Run all tests:**
   ```bash
   ./run-tests.sh
   ```
   Or directly:
   ```bash
   vendor/bin/phpunit
   ```

## Test Structure

The test suite is organized as follows:

```
tests/
├── bootstrap.php          # Test environment setup
├── TestCase.php           # Base test case with helpers
├── Forms/                 # Form-specific tests
│   ├── LoginFormTest.php
│   ├── RegistrationFormTest.php
│   ├── ContactFormTest.php
│   ├── StaffCreateFormTest.php
│   ├── ProfileFormTest.php
│   └── RequestAccessFormTest.php
└── README.md              # Detailed test documentation
```

## What Gets Tested

### All Forms Test:

1. **Form Display** - Ensures forms render correctly with all required fields
2. **CSRF Protection** - Verifies CSRF tokens are required and validated
3. **Field Validation** - Tests all validation rules for each field
4. **Required Fields** - Ensures required fields are enforced
5. **Optional Fields** - Verifies optional fields work correctly
6. **Error Handling** - Tests error messages display correctly
7. **Rate Limiting** - Verifies rate limiting works (where applicable)
8. **File Uploads** - Tests file upload handling (where applicable)
9. **Success Scenarios** - Tests successful form submissions

### Specific Form Tests:

#### Login Form
- ✅ Form displays correctly
- ✅ CSRF token required
- ✅ Email validation
- ✅ Password validation
- ✅ Invalid credentials handling
- ✅ Rate limiting (5 attempts per 15 minutes)

#### Registration Form
- ✅ Form displays correctly
- ✅ CSRF token required
- ✅ Required fields validation
- ✅ Password matching validation
- ✅ Password length validation
- ✅ Rate limiting (3 attempts per hour)

#### Contact Form
- ✅ Form displays correctly
- ✅ CSRF token required
- ✅ Name validation
- ✅ Email format validation
- ✅ Subject validation
- ✅ Message validation
- ✅ Rate limiting (3 submissions per hour)

#### Staff Create Form
- ✅ Form displays correctly
- ✅ CSRF token required
- ✅ Required fields (first_name, last_name)
- ✅ Optional fields handling
- ✅ Date field validation

#### Profile Form
- ✅ Form displays correctly
- ✅ CSRF token required
- ✅ Email validation
- ✅ File upload handling (photos)
- ✅ Signature upload handling

#### Request Access Form
- ✅ Form displays correctly
- ✅ CSRF token required
- ✅ Required fields validation
- ✅ Email validation
- ✅ Seats validation

## Running Tests

### Run All Tests
```bash
./run-tests.sh
```

### Run with Coverage
```bash
./run-tests.sh --coverage
```
This generates an HTML coverage report in `coverage/` directory.

### Run Form Tests Only
```bash
./run-tests.sh --forms
```

### Run Specific Test File
```bash
./run-tests.sh tests/Forms/LoginFormTest.php
```

### Run with Verbose Output
```bash
./run-tests.sh --verbose
```

## Test Environment

Tests use:
- **Output Buffering** - To capture form output
- **Session Simulation** - To test authentication states
- **Request Simulation** - By setting `$_POST`, `$_GET`, `$_SERVER`
- **CSRF Token Generation** - For testing form submissions

## Adding New Tests

When adding a new form or modifying existing forms:

1. **Create test file** in `tests/Forms/YourFormTest.php`
2. **Extend TestCase** class
3. **Test all aspects:**
   - Form display
   - CSRF protection
   - All field validations
   - Success scenarios
   - Error scenarios
   - Rate limiting (if applicable)
   - File uploads (if applicable)

Example:
```php
<?php
namespace Tests\Forms;

use Tests\TestCase;

class YourFormTest extends TestCase
{
    public function testFormDisplays()
    {
        $this->simulateGet();
        ob_start();
        include dirname(__DIR__, 2) . '/public/your-form.php';
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<form', $output);
    }
    
    public function testFormRequiresCsrfToken()
    {
        $this->simulatePost(['field' => 'value']);
        // ... test CSRF validation
    }
}
```

## Continuous Testing

For continuous testing during development:

```bash
# Watch for changes and run tests (requires entr or similar)
find tests/ public/ -name "*.php" | entr ./run-tests.sh
```

## Test Maintenance

- **Keep tests updated** when forms change
- **Add tests** for new forms immediately
- **Fix failing tests** before deploying
- **Review coverage** regularly to ensure all forms are tested

## Troubleshooting

### Tests fail with "Class not found"
- Run `composer install` to install dependencies

### Tests fail with database errors
- Ensure database is set up correctly
- Check `config/config.php` for database settings
- Some tests may require test data setup

### Tests show "Invalid security token"
- This is expected - tests verify CSRF protection works
- Tests generate valid tokens using `getCsrfToken()`

## Best Practices

1. **Test early, test often** - Run tests after every form change
2. **Test edge cases** - Empty fields, invalid formats, etc.
3. **Test security** - CSRF, rate limiting, validation
4. **Keep tests simple** - One assertion per test when possible
5. **Use descriptive names** - Test names should describe what they test

## Next Steps

Consider adding:
- Integration tests for API endpoints
- Database tests for models
- End-to-end tests with browser automation
- Performance tests for form submissions
- Security tests for vulnerabilities


