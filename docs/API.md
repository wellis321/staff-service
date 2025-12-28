# Staff Service API Reference

## Overview

The Staff Service API provides programmatic access to staff data for integration with other applications and external systems. The API uses REST principles and returns JSON responses.

**Base URL**: `https://your-domain.com/api/`

**API Version**: v1 (currently)

## Authentication

The API supports two authentication methods:

### 1. API Key Authentication (Recommended for External Systems)

Include your API key in one of the following ways:

- **Authorization Header**: `Authorization: Bearer <your-api-key>` or `Authorization: ApiKey <your-api-key>`
- **X-API-Key Header**: `X-API-Key: <your-api-key>`
- **Query Parameter**: `?api_key=<your-api-key>` (less secure, convenient for testing)

### 2. Session Authentication (For Web Apps)

If you're making requests from the same domain as the Staff Service, you can use session-based authentication by logging in via the web interface first.

## Rate Limiting

API requests are currently not rate-limited, but limits may be introduced in future versions. We recommend:

- Implementing client-side rate limiting
- Caching responses when appropriate
- Using webhooks for real-time updates instead of polling

## Endpoints

### Get Staff Member by ID

Retrieve a single staff member's details.

**Endpoint**: `GET /api/staff-data.php?id={id}`

**Parameters**:
- `id` (required): Staff member person ID

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "organisation_id": 1,
    "person_type": "staff",
    "user_id": 5,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "01234567890",
    "employee_reference": "EMP001",
    "is_active": true,
    "job_title": "Support Worker",
    "employment_start_date": "2024-01-15",
    "organisational_units": [
      {
        "id": 1,
        "organisational_unit_id": 3,
        "unit_name": "Care Team A",
        "role_in_unit": "member",
        "is_primary": true
      }
    ]
  }
}
```

### List Staff Members

Retrieve a list of staff members with filtering and pagination.

**Endpoint**: `GET /api/staff-data.php`

**Query Parameters**:
- `search` (optional): Search by name, email, or employee reference
- `include_inactive` (optional): Set to `1` to include inactive staff
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 20, max: 100)
- `organisational_unit_id` (optional): Filter by organisational unit ID

**Example Request**:
```
GET /api/staff-data.php?page=1&limit=20&include_inactive=0
```

**Response**:
```json
{
  "success": true,
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "total_pages": 3,
    "has_next": true,
    "has_prev": false
  },
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "employee_reference": "EMP001",
      "job_title": "Support Worker",
      "is_active": true,
      "organisational_units": [...]
    }
  ]
}
```

### Search Staff

Search for staff members by name, email, or employee reference.

**Endpoint**: `GET /api/staff-data.php?search={query}`

**Example**:
```
GET /api/staff-data.php?search=john
```

## Webhooks

Webhooks allow external systems to receive real-time notifications when staff data changes.

### Subscribe to Webhooks

**Endpoint**: `POST /api/webhooks.php`

**Request Body**:
```json
{
  "action": "subscribe",
  "url": "https://your-system.com/webhooks/staff-updates",
  "events": ["person.created", "person.updated", "person.deactivated"],
  "secret": "your-webhook-secret-for-verification"
}
```

**Supported Events**:
- `person.created`: New staff member created
- `person.updated`: Staff member updated
- `person.deactivated`: Staff member deactivated
- `person.photo.approved`: Staff photo approved
- `person.unit.assigned`: Staff assigned to organisational unit
- `person.unit.removed`: Staff removed from organisational unit

### Webhook Payload

When an event occurs, Staff Service will send a POST request to your webhook URL with the following payload:

```json
{
  "event": "person.updated",
  "timestamp": "2024-01-15T10:30:00Z",
  "organisation_id": 1,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    ...
  }
}
```

The request will include an `X-Webhook-Signature` header containing an HMAC-SHA256 signature of the payload using your webhook secret.

### Verify Webhook Signature

```php
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    // Invalid signature
    http_response_code(401);
    exit;
}
```

## Error Responses

All error responses follow this format:

```json
{
  "error": "Error type",
  "message": "Detailed error message"
}
```

**HTTP Status Codes**:
- `200`: Success
- `400`: Bad Request (invalid parameters)
- `401`: Unauthorized (authentication required)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found (resource doesn't exist)
- `500`: Internal Server Error

**Example Error Response**:
```json
{
  "error": "Person not found",
  "message": "The requested staff member does not exist or you don't have access to it"
}
```

## Data Models

### Staff Member Object

```json
{
  "id": 1,
  "organisation_id": 1,
  "person_type": "staff",
  "user_id": 5,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "01234567890",
  "date_of_birth": "1990-01-15",
  "employee_reference": "EMP001",
  "is_active": true,
  "photo_path": "person_1_1234567890.jpg",
  "photo_approval_status": "approved",
  "job_title": "Support Worker",
  "employment_start_date": "2024-01-15",
  "employment_end_date": null,
  "line_manager_id": 2,
  "emergency_contact_name": "Jane Doe",
  "emergency_contact_phone": "09876543210",
  "organisational_units": [
    {
      "id": 1,
      "organisational_unit_id": 3,
      "unit_name": "Care Team A",
      "unit_code": "CTA",
      "role_in_unit": "member",
      "is_primary": true,
      "start_date": "2024-01-15",
      "end_date": null
    }
  ],
  "created_at": "2024-01-15T10:00:00Z",
  "updated_at": "2024-01-15T12:30:00Z"
}
```

## Examples

### PHP Example

```php
<?php
$apiKey = 'your-api-key';
$url = 'https://staff.example.com/api/staff-data.php?page=1&limit=20';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    foreach ($data['data'] as $staff) {
        echo $staff['first_name'] . ' ' . $staff['last_name'] . "\n";
    }
}
?>
```

### JavaScript Example

```javascript
const apiKey = 'your-api-key';
const url = 'https://staff.example.com/api/staff-data.php?page=1&limit=20';

fetch(url, {
    headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        data.data.forEach(staff => {
            console.log(`${staff.first_name} ${staff.last_name}`);
        });
    }
});
```

### Python Example

```python
import requests

api_key = 'your-api-key'
url = 'https://staff.example.com/api/staff-data.php'
headers = {
    'Authorization': f'Bearer {api_key}',
    'Content-Type': 'application/json'
}
params = {
    'page': 1,
    'limit': 20
}

response = requests.get(url, headers=headers, params=params)
data = response.json()

if data['success']:
    for staff in data['data']:
        print(f"{staff['first_name']} {staff['last_name']}")
```

## API Keys Management

API keys can be managed through the Staff Service admin interface (coming soon). For now, contact your system administrator to create API keys.

## Best Practices

1. **Store API keys securely**: Never commit API keys to version control
2. **Use HTTPS**: Always use HTTPS when making API requests
3. **Cache responses**: Cache staff data locally to reduce API calls
4. **Handle errors gracefully**: Implement proper error handling and retry logic
5. **Use webhooks**: Subscribe to webhooks instead of polling for updates
6. **Respect pagination**: Use pagination for large datasets

## Support

For API support, contact: [CONTACT_EMAIL from .env]

