# External Systems Integration

This document describes how to integrate Staff Service with external systems like Entra/Microsoft 365, HR systems, Finance systems, and LMS systems.

## Overview

Staff Service acts as the central hub for staff data, feeding information to external systems and receiving updates from recruitment systems. This centralised approach ensures data consistency and reduces duplication.

## Integration Architecture

```
┌─────────────────────┐
│  Recruitment System │
│      (Input)        │
└──────────┬──────────┘
           │
           │ Push new hires
           ▼
┌─────────────────────┐
│   Staff Service     │
│  (Source of Truth)  │
└──────────┬──────────┘
           │
           ├───► Microsoft Entra/365
           ├───► HR Systems
           ├───► Finance Systems
           └───► LMS Systems
```

## Integration Patterns

### Pattern 1: Staff Service → Microsoft Entra/365

Synchronise staff data with Microsoft Entra ID (Azure AD) for user account management.

**Data Flow**:
1. Staff Service exports user data via Microsoft Graph API
2. Syncs: user accounts, groups, organisational structure
3. Bidirectional sync for organisational units

**Implementation Steps**:
1. Configure Entra ID application registration
2. Obtain client ID, tenant ID, and client secret
3. Set up sync job to run periodically
4. Map Staff Service staff to Entra users
5. Handle updates in both directions

**Configuration**:
```env
ENTRA_SYNC_ENABLED=true
ENTRA_TENANT_ID=your-tenant-id
ENTRA_CLIENT_ID=your-client-id
ENTRA_CLIENT_SECRET=your-client-secret
ENTRA_SYNC_INTERVAL=3600
```

**Data Mapped**:
- Name → Display Name
- Email → User Principal Name
- Employee Reference → Employee ID attribute
- Organisational Units → Groups
- Job Title → Job Title attribute

### Pattern 2: Staff Service → HR Systems

Provide staff data to HR/payroll systems.

**Data Flow**:
1. HR system pulls staff data via API
2. Syncs: employment dates, job titles, line managers, salary grades
3. HR system pushes: payroll updates, contract changes (future)

**Implementation Steps**:
1. HR system creates API key in Staff Service
2. HR system queries Staff Service API for staff list
3. Maps staff data to HR system format
4. Imports into HR system
5. Sets up periodic sync

**API Usage**:
```
GET /api/staff-data.php?include_inactive=0
```

**Data Mapped**:
- Employee Reference → Employee Number
- Employment Start Date → Start Date
- Employment End Date → End Date
- Job Title → Position
- Line Manager → Manager

### Pattern 3: Staff Service → Finance Systems

Provide staff data to finance/payroll systems.

**Data Flow**:
1. Finance system pulls staff data for payroll processing
2. Syncs: employee references, cost centres, organisational units
3. Finance system maintains: salary, expenses, invoices

**Implementation Steps**:
1. Finance system creates API key in Staff Service
2. Finance system queries Staff Service API
3. Maps organisational units to cost centres
4. Links staff to payroll records

**Data Mapped**:
- Employee Reference → Payroll ID
- Organisational Units → Cost Centres
- Employment Status → Payroll Status

### Pattern 4: Staff Service → LMS Systems

Provide staff data to Learning Management Systems for course assignments.

**Data Flow**:
1. LMS pulls staff data for course assignments
2. Syncs: staff list, organisational units, job roles
3. LMS maintains: training records, certifications

**Implementation Steps**:
1. LMS creates API key in Staff Service
2. LMS queries Staff Service API for active staff
3. Maps staff to learners/users in LMS
4. Assigns courses based on organisational units or job roles

**Data Mapped**:
- Staff Member → Learner/User
- Job Title → Job Role in LMS
- Organisational Units → Groups/Departments

### Pattern 5: Recruitment System → Staff Service

Receive new hire data from recruitment systems.

**Data Flow**:
1. Recruitment system pushes new hires via API
2. Creates staff profiles with: name, start date, job title, department
3. Staff Service validates and creates person record
4. Triggers onboarding workflow

**Implementation Steps**:
1. Recruitment system obtains API key from Staff Service
2. Recruitment system sends POST request when new hire is confirmed
3. Staff Service validates data
4. Staff Service creates person record
5. Staff Service may create user account (if configured)
6. Staff Service sends confirmation back to recruitment system

**API Endpoint** (to be implemented):
```
POST /api/recruitment/import
```

**Request Format**:
```json
{
  "source_system": "recruitment-system-v1",
  "new_hire": {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "employee_reference": "EMP001",
    "job_title": "Support Worker",
    "employment_start_date": "2024-02-01",
    "organisational_unit": "Care Team A",
    "department": "Adult Services"
  }
}
```

## Webhook Integration

External systems can subscribe to webhooks for real-time updates.

### Subscribe to Webhooks

**Endpoint**: `POST /api/webhooks.php`

**Request**:
```json
{
  "action": "subscribe",
  "url": "https://your-system.com/webhooks/staff-updates",
  "events": [
    "person.created",
    "person.updated",
    "person.deactivated"
  ],
  "secret": "your-webhook-secret"
}
```

### Webhook Events

- `person.created`: New staff member created
- `person.updated`: Staff member updated
- `person.deactivated`: Staff member deactivated
- `person.photo.approved`: Staff photo approved
- `person.unit.assigned`: Staff assigned to organisational unit
- `person.unit.removed`: Staff removed from organisational unit

### Webhook Payload

```json
{
  "event": "person.updated",
  "timestamp": "2024-01-15T10:30:00Z",
  "organisation_id": 1,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "employee_reference": "EMP001",
    "job_title": "Senior Support Worker",
    ...
  }
}
```

## API Key Management

Each external system should have its own API key for authentication and audit purposes.

**Creating API Keys** (via admin interface - coming soon):
1. Log in to Staff Service as administrator
2. Navigate to API Keys section
3. Create new API key with descriptive name
4. Set expiration date (optional)
5. Copy API key (shown only once)

**API Key Best Practices**:
- Use separate API keys for each system
- Rotate API keys periodically
- Set expiration dates
- Revoke keys immediately if compromised
- Monitor API key usage

## Sync Scheduling

External systems should sync staff data periodically. Recommended intervals:

- **Entra/365**: Every 1-4 hours (user account management)
- **HR Systems**: Daily (payroll processing)
- **Finance Systems**: Daily (payroll processing)
- **LMS Systems**: Weekly (course assignments)
- **Recruitment Systems**: Real-time via API (new hires)

## Error Handling

External systems should implement proper error handling:

1. **API Errors**: Handle HTTP error codes (401, 404, 500, etc.)
2. **Network Errors**: Implement retry logic with exponential backoff
3. **Data Validation**: Validate data received from Staff Service
4. **Logging**: Log all sync attempts and errors
5. **Alerts**: Set up alerts for sync failures

## Data Mapping

Each external system may require different field mappings. Document your mappings:

| Staff Service Field | External System Field | Notes |
|---------------------|----------------------|-------|
| employee_reference | Employee Number | Primary identifier |
| first_name + last_name | Full Name | May need concatenation |
| email | Email Address | |
| job_title | Position/Title | |
| organisational_units | Department/Groups | May need transformation |

## Security Considerations

1. **API Keys**: Store securely, never in version control
2. **HTTPS**: Always use HTTPS for API calls
3. **Webhook Verification**: Always verify webhook signatures
4. **Rate Limiting**: Respect rate limits (to be implemented)
5. **Data Privacy**: Only sync necessary data, respect GDPR

## Monitoring and Reporting

Staff Service tracks external system syncs in the `external_system_sync` table:

- Last sync timestamp
- Sync status (active, pending, failed, disabled)
- Error messages
- Sync metadata

Monitor this table to track integration health.

## Testing

Before going live:

1. Test API connectivity
2. Test data mapping accuracy
3. Test error handling
4. Test webhook delivery
5. Verify data integrity after sync
6. Test rollback procedures

## Support

For integration support, contact: [CONTACT_EMAIL from .env]

## Related Documentation

- [API Reference](API.md) - Complete API documentation
- [Integration Patterns](INTEGRATION.md) - General integration guide
- [Architecture](ARCHITECTURE.md) - Architecture overview

