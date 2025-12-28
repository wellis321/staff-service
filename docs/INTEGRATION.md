# Integration Patterns

This document describes how to integrate other applications with the Staff Service.

## Integration Overview

The Staff Service is designed to be the central source of truth for staff data. Other applications can integrate with it in two modes:

1. **Standalone Mode**: App operates independently with its own staff data
2. **Integrated Mode**: App uses Staff Service as the source of truth for staff data

## Integration Modes

### Standalone Mode

When Staff Service is not available or not configured, your app should operate independently:

- Create and manage staff in your own database tables
- No external API calls required
- Self-contained functionality

**Configuration**:
```env
USE_STAFF_SERVICE=false
```

### Integrated Mode

When Staff Service is available, your app should prefer it as the source of truth:

- Read staff data from Staff Service API
- Cache staff data locally for performance
- Sync periodically or via webhooks
- Write operations go to Staff Service (except app-specific data)

**Configuration**:
```env
USE_STAFF_SERVICE=true
STAFF_SERVICE_URL=https://staff.example.com
STAFF_SERVICE_API_KEY=your-api-key
STAFF_SYNC_INTERVAL=3600
```

## Integration Patterns

### Pattern 1: Digital ID Integration

Digital ID needs staff data for ID card generation and verification.

**Data Flow**:
1. Digital ID queries Staff Service API for staff profiles
2. Maps Staff Service `people.id` to Digital ID `employees.id`
3. Syncs: name, photo, employee reference, organisational units
4. Digital ID maintains: ID card data, verification logs, check-ins

**Implementation**:
- Create `StaffServiceClient` class in Digital ID
- Check `USE_STAFF_SERVICE` configuration flag
- Fall back to local `employees` table if Staff Service unavailable
- Sync staff data on-demand or periodically

**Example Code Structure**:
```php
// Digital ID StaffServiceClient class
class StaffServiceClient {
    public static function getStaffMember($personId) {
        if (!USE_STAFF_SERVICE) {
            return self::getFromLocalDatabase($personId);
        }
        
        // Fetch from Staff Service API
        return self::fetchFromAPI($personId);
    }
}
```

### Pattern 2: Medication App Integration (Future)

Medication app needs staff data to identify who can administer medication.

**Data Flow**:
1. Medication app queries Staff Service for staff who can administer medication
2. Syncs: name, photo, qualifications, organisational units
3. Medication app maintains: medication records, administration logs

### Pattern 3: Pre-populated Forms

Other apps can pre-populate forms with staff data from Staff Service.

**Data Flow**:
1. User selects staff member in your app
2. App queries Staff Service API for staff details
3. Form fields pre-populate with: name, email, phone, employee reference, etc.
4. User completes app-specific information
5. Submit form with reference to Staff Service person ID

## Data Synchronisation Strategies

### Strategy 1: Pull Model (On-Demand)

Fetch staff data from Staff Service API when needed.

**Pros**:
- Always up-to-date
- No local storage required
- Simple implementation

**Cons**:
- Requires API call on every request
- Slower response times
- API dependency

**Use When**:
- Real-time data is critical
- Low request volume
- Staff data changes frequently

### Strategy 2: Pull Model (Periodic Sync)

Fetch staff data periodically and cache locally.

**Pros**:
- Fast response times
- Reduces API calls
- Works offline after sync

**Cons**:
- Data may be stale
- Requires storage
- More complex implementation

**Use When**:
- High request volume
- Data doesn't change frequently
- Offline capability needed

### Strategy 3: Push Model (Webhooks)

Receive real-time updates via webhooks.

**Pros**:
- Real-time updates
- Efficient
- Event-driven

**Cons**:
- Requires webhook infrastructure
- More complex to implement
- Network dependency

**Use When**:
- Real-time updates required
- Event-driven architecture
- High staff update frequency

### Strategy 4: Hybrid Model (Recommended)

Combine periodic sync with webhooks.

**Pros**:
- Best of both worlds
- Fast response times
- Real-time updates
- Offline capability

**Cons**:
- More complex
- Requires both sync and webhook code

**Implementation**:
1. Periodic sync (e.g., hourly) for bulk data
2. Webhooks for real-time updates
3. Local cache with TTL

## Implementation Guide

### Step 1: Create API Client Class

Create a class to handle Staff Service API communication:

```php
class StaffServiceClient {
    private static $baseUrl;
    private static $apiKey;
    
    public static function init() {
        self::$baseUrl = getenv('STAFF_SERVICE_URL');
        self::$apiKey = getenv('STAFF_SERVICE_API_KEY');
    }
    
    public static function getStaffMember($personId) {
        $url = self::$baseUrl . '/api/staff-data.php?id=' . $personId;
        return self::makeRequest($url);
    }
    
    public static function searchStaff($query) {
        $url = self::$baseUrl . '/api/staff-data.php?search=' . urlencode($query);
        return self::makeRequest($url);
    }
    
    private static function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
}
```

### Step 2: Add Configuration

Add Staff Service configuration to your app's `.env`:

```env
USE_STAFF_SERVICE=true
STAFF_SERVICE_URL=https://staff.example.com
STAFF_SERVICE_API_KEY=your-api-key-here
STAFF_SYNC_INTERVAL=3600
```

### Step 3: Implement Fallback Logic

Ensure your app works even if Staff Service is unavailable:

```php
function getStaffMember($personId) {
    if (USE_STAFF_SERVICE && StaffServiceClient::isAvailable()) {
        $staff = StaffServiceClient::getStaffMember($personId);
        if ($staff) {
            return $staff;
        }
    }
    
    // Fallback to local database
    return getStaffFromLocalDatabase($personId);
}
```

### Step 4: Implement Caching

Cache staff data locally to improve performance:

```php
function getStaffMemberCached($personId) {
    $cacheKey = "staff_{$personId}";
    $cached = getFromCache($cacheKey);
    
    if ($cached && !isCacheExpired($cacheKey)) {
        return $cached;
    }
    
    $staff = getStaffMember($personId);
    if ($staff) {
        setCache($cacheKey, $staff, 3600); // Cache for 1 hour
    }
    
    return $staff;
}
```

### Step 5: Handle Webhooks (Optional)

If using webhooks, implement a webhook endpoint:

```php
// webhook-handler.php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

// Verify signature
if (!verifyWebhookSignature($payload, $signature)) {
    http_response_code(401);
    exit;
}

$event = json_decode($payload, true);

switch ($event['event']) {
    case 'person.updated':
        // Update local cache
        invalidateCache("staff_{$event['data']['id']}");
        break;
    case 'person.created':
        // Add to local cache
        break;
}
```

## Error Handling

Always implement proper error handling:

```php
function getStaffMemberSafe($personId) {
    try {
        return StaffServiceClient::getStaffMember($personId);
    } catch (ApiException $e) {
        error_log("Staff Service API error: " . $e->getMessage());
        // Fallback to local database
        return getStaffFromLocalDatabase($personId);
    } catch (Exception $e) {
        error_log("Unexpected error: " . $e->getMessage());
        return null;
    }
}
```

## Migration Strategy

When migrating an existing app to use Staff Service:

1. **Phase 1**: Deploy Staff Service and import existing staff data
2. **Phase 2**: Enable Staff Service integration in your app (with fallback)
3. **Phase 3**: Verify integration works correctly
4. **Phase 4**: Gradually migrate staff management to Staff Service
5. **Phase 5**: Remove local staff creation/editing (optional)

## Testing

Test your integration:

1. Test with Staff Service available
2. Test with Staff Service unavailable (fallback)
3. Test API error scenarios
4. Test webhook delivery
5. Test caching behavior

## Related Documentation

- [API Reference](API.md) - Complete API documentation
- [Architecture](ARCHITECTURE.md) - Architecture overview
- [External Systems](EXTERNAL_SYSTEMS.md) - External system integration

