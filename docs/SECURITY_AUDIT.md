# Security Audit Report - People Management Service

**Date:** 2025-01-27  
**Version Reviewed:** 1.0.0  
**Status:** Production Readiness Assessment

## Executive Summary

The People Management Service demonstrates **good foundational security practices** with proper authentication, CSRF protection, and SQL injection prevention. However, several **medium-priority security improvements** are recommended before production deployment, particularly around API security, rate limiting, and session management.

**Overall Security Rating:** ⚠️ **Good with Recommendations** (7/10)

---

## Security Strengths ✅

### 1. Authentication & Authorization
- ✅ **Password Security**: Strong password requirements (min 8 chars, uppercase, lowercase, number, special)
- ✅ **Password Hashing**: Uses bcrypt via shared-auth package
- ✅ **Session Security**: HttpOnly cookies, secure cookies in production, cookie-only sessions
- ✅ **RBAC**: Role-based access control implemented via shared-auth
- ✅ **Multi-tenant Isolation**: Organisation-level data isolation enforced

### 2. SQL Injection Prevention
- ✅ **Prepared Statements**: All database queries use PDO prepared statements
- ✅ **Parameter Binding**: Proper parameter binding throughout codebase
- ✅ **No Direct SQL Concatenation**: No evidence of SQL string concatenation

### 3. CSRF Protection
- ✅ **CSRF Tokens**: All forms use CSRF token validation via `CSRF::validatePost()`
- ✅ **Token Generation**: Proper token generation and validation
- ✅ **Consistent Implementation**: CSRF protection applied across all forms

### 4. File Upload Security
- ✅ **File Type Validation**: MIME type validation using `finfo_open()`
- ✅ **File Size Limits**: Enforced limits (5MB photos, 2MB signatures)
- ✅ **Directory Traversal Protection**: Path sanitization in `view-image.php`
- ✅ **Access Control**: Files protected by authentication and organisation checks
- ✅ **Unique Filenames**: Timestamp-based unique filenames prevent overwrites

### 5. XSS Prevention
- ✅ **Output Escaping**: Extensive use of `htmlspecialchars()` throughout (321 instances found)
- ✅ **Context-Aware Escaping**: Proper escaping in HTML contexts

### 6. Error Handling
- ✅ **Production Error Handling**: Errors hidden in production, logged in development
- ✅ **Environment-Based Config**: Different error reporting for dev/prod

---

## Security Issues & Recommendations 🔴

### Critical Priority

#### 1. CORS Configuration - Wildcard Origin
**Location:** `public/api/staff-data.php:26`, `public/api/recruitment/import.php:14`

**Issue:**
```php
header('Access-Control-Allow-Origin: *');
```

**Risk:** Allows any origin to access the API, potentially enabling CSRF attacks from malicious websites.

**Recommendation:**
- Implement origin whitelist based on environment variable
- Use specific allowed origins instead of wildcard
- Consider requiring preflight requests for sensitive operations

**Priority:** High (if API is publicly accessible)

---

### High Priority

#### 2. Missing Session Regeneration on Login
**Location:** Authentication flow (shared-auth package)

**Issue:** No evidence of `session_regenerate_id()` being called after successful login.

**Risk:** Session fixation attacks - attacker could fixate a session ID and use it after user logs in.

**Recommendation:**
- Call `session_regenerate_id(true)` immediately after successful authentication
- Implement in `Auth::login()` method or immediately after in login handlers

**Priority:** High

#### 3. No Rate Limiting
**Location:** All endpoints, especially login, registration, API endpoints

**Issue:** No rate limiting implemented on authentication endpoints or API endpoints.

**Risk:** 
- Brute force attacks on login
- API abuse/DoS
- Registration spam

**Recommendation:**
- Implement rate limiting middleware
- Use Redis or database-based rate limiting
- Recommended limits:
  - Login: 5 attempts per 15 minutes per IP
  - Registration: 3 attempts per hour per IP
  - API: 100 requests per minute per API key
  - Contact form: 3 submissions per hour per IP

**Priority:** High

#### 4. API Key in Query String
**Location:** `src/classes/ApiAuth.php:71`

**Issue:**
```php
return $_GET['api_key'] ?? null;
```

**Risk:** API keys exposed in URLs, browser history, server logs, referrer headers.

**Recommendation:**
- Remove query parameter support for API keys
- Only support header-based authentication (Authorization header, X-API-Key header)
- Document deprecation if currently in use
- Add warning in API documentation

**Priority:** High

---

### Medium Priority

#### 5. Error Message Information Disclosure
**Location:** Various API endpoints and error handlers

**Issue:** Some error messages may reveal internal structure or database details.

**Examples:**
- `public/api/staff-data.php:159` - Exception messages returned to client
- Database error details potentially exposed

**Risk:** Information leakage that could aid attackers.

**Recommendation:**
- Return generic error messages to clients
- Log detailed errors server-side only
- Use error codes instead of detailed messages
- Implement consistent error response format

**Priority:** Medium

#### 6. Missing .htaccess Protection for Uploads
**Location:** `uploads/` directory

**Issue:** No `.htaccess` file to prevent direct PHP execution in uploads directory.

**Risk:** If malicious files are uploaded, they could be executed if PHP is misconfigured.

**Recommendation:**
- Create `.htaccess` in uploads directory:
  ```apache
  php_flag engine off
  Options -ExecCGI
  RemoveHandler .php .phtml .php3 .php4 .php5 .phps .cgi .exe .pl .asp .aspx .shtml .shtm .fcgi .fpl .jsp .htm .html .js
  ```
- Ensure uploads directory is outside document root if possible
- Or use a proxy script (like `view-image.php`) for all file access

**Priority:** Medium

#### 7. File Upload Directory Permissions
**Location:** File upload handlers

**Issue:** Directories created with `0755` permissions - should verify they're not world-writable.

**Recommendation:**
- Ensure upload directories have correct permissions (0755 is acceptable, but verify)
- Ensure files are not executable
- Consider using `umask()` to set default permissions

**Priority:** Medium

#### 8. Missing Content Security Policy (CSP)
**Location:** All pages

**Issue:** No Content Security Policy headers set.

**Risk:** XSS attacks could be more effective without CSP restrictions.

**Recommendation:**
- Implement CSP headers
- Start with restrictive policy, relax as needed
- Example:
  ```php
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; img-src 'self' data:;");
  ```

**Priority:** Medium

#### 9. Missing Security Headers
**Location:** All pages

**Issue:** Missing several security headers.

**Recommendation:**
Add the following headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY` (or SAMEORIGIN if needed for iframes)
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (HTTPS only)

**Priority:** Medium

#### 10. API Error Response Consistency
**Location:** `public/api/staff-data.php`, other API endpoints

**Issue:** Some API endpoints may return different error formats.

**Recommendation:**
- Standardize API error response format
- Use consistent HTTP status codes
- Document error codes in API documentation

**Priority:** Medium

---

### Low Priority / Best Practices

#### 11. Input Validation Enhancement
**Location:** Various form handlers

**Issue:** Some inputs could benefit from stricter validation (e.g., email format, phone number format).

**Recommendation:**
- Add stricter validation for email addresses
- Validate phone number formats
- Sanitize and validate all user inputs
- Consider using validation library

**Priority:** Low

#### 12. Logging & Monitoring
**Location:** Application-wide

**Issue:** Limited structured logging for security events.

**Recommendation:**
- Log all authentication attempts (success and failure)
- Log all API key usage
- Log sensitive operations (profile updates, data exports)
- Implement log rotation
- Consider security event monitoring

**Priority:** Low (but important for compliance)

#### 13. Password Reset Functionality
**Location:** Not found in codebase

**Issue:** No password reset functionality visible.

**Recommendation:**
- Implement secure password reset flow
- Use time-limited tokens
- Send reset links via email
- Require old password for password changes

**Priority:** Low (if not needed)

#### 14. Two-Factor Authentication (2FA)
**Location:** Not implemented

**Issue:** No 2FA support.

**Recommendation:**
- Consider implementing 2FA for admin accounts
- Use TOTP (Time-based One-Time Password)
- Make 2FA optional but recommended for admins

**Priority:** Low (enhancement)

#### 15. API Versioning
**Location:** API endpoints

**Issue:** API versioning not clearly implemented.

**Recommendation:**
- Implement API versioning (e.g., `/api/v1/staff-data.php`)
- Document versioning strategy
- Plan for backward compatibility

**Priority:** Low

---

## Production Readiness Checklist

### Must Fix Before Production
- [ ] Fix CORS wildcard (use specific origins)
- [ ] Implement session regeneration on login
- [ ] Add rate limiting to authentication endpoints
- [ ] Remove API key from query string support
- [ ] Add .htaccess protection for uploads directory
- [ ] Add security headers (CSP, X-Frame-Options, etc.)

### Should Fix Before Production
- [ ] Improve error message handling (generic messages)
- [ ] Verify file upload permissions
- [ ] Add structured security logging
- [ ] Standardize API error responses

### Nice to Have
- [ ] Implement password reset functionality
- [ ] Add 2FA support
- [ ] Enhanced input validation
- [ ] API versioning

---

## Security Testing Recommendations

1. **Penetration Testing**: Engage security professionals for penetration testing
2. **OWASP Top 10**: Review against OWASP Top 10 vulnerabilities
3. **Dependency Scanning**: Scan for vulnerable dependencies
4. **Code Review**: Conduct thorough security code review
5. **Load Testing**: Test rate limiting and DoS protections

---

## Compliance Considerations

### GDPR Compliance
- ✅ Data isolation by organisation
- ✅ Access controls in place
- ⚠️ Consider data export functionality
- ⚠️ Consider right to deletion implementation
- ⚠️ Document data retention policies

### Data Protection
- ✅ Secure data storage
- ✅ Access logging (needs enhancement)
- ⚠️ Encryption at rest (verify database encryption)
- ⚠️ Encryption in transit (HTTPS required)

---

## Conclusion

The People Management Service has a **solid security foundation** with proper authentication, CSRF protection, and SQL injection prevention. The identified issues are primarily **enhancements and best practices** rather than critical vulnerabilities.

**Recommendation:** Address the **Critical** and **High Priority** items before production deployment. The **Medium Priority** items should be addressed in the first production release cycle.

**Estimated Effort:**
- Critical/High Priority: 2-3 days
- Medium Priority: 3-5 days
- Low Priority: Ongoing improvements

---

## Review Notes

- Codebase reviewed: January 2025
- Focus areas: Authentication, API security, file uploads, session management
- Shared-auth package security not reviewed (assumed secure)
- Database security configuration not reviewed (assumed properly configured)


