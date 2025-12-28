# Staff Service Architecture

## Overview

The Staff Service (People Management Service) is designed to be the **central source of truth** for staff data across all applications. While each application (Digital ID, Medication App, etc.) can operate **standalone**, when Staff Service is present, it becomes the preferred source for staff information, enabling pre-populated forms, monitoring, reporting, and integration with external systems.

## Architecture Principles

### 1. Standalone Capability

- Each app can function independently without Staff Service
- Each app maintains its own authentication (using shared-auth)
- Apps have their own databases/tables for their specific domain data
- No hard dependencies between apps

### 2. Integration Preference

- When Staff Service exists, other apps should prefer Staff Service as source of truth
- Integration via REST API (JSON)
- Staff Service provides read-only API endpoints for staff data
- Apps can cache staff data locally but should sync periodically

### 3. Central Hub Pattern

- Staff Service sits at the center of the ecosystem
- Feeds data to: Digital ID, Medication App, Entra/Microsoft 365, HR systems, Finance systems, LMS systems
- Receives initial data from: Recruitment system
- Enables centralised reporting and monitoring

## Current State Analysis

### Digital ID App

- **Tables**: `employees`, `digital_id_cards`, `verification_logs`, `check_ins`, `entra_sync`
- **Purpose**: Digital ID card management, verification, audit trails
- **Staff Data**: Currently maintains its own `employees` table linked to `users`

### Staff Service App

- **Tables**: `people`, `staff_profiles`, `person_organisational_units`, `api_keys`, `webhook_subscriptions`, `external_system_sync`, `recruitment_imports`
- **Purpose**: Comprehensive staff profile management
- **Staff Data**: Central repository for all staff information

### Shared Foundation

- **Tables**: `users`, `organisations`, `roles`, `user_roles`, `organisational_units` (from shared-auth)
- **Purpose**: Authentication and organisational structure
- **Shared by**: All apps via shared-auth package

## Recommended Architecture

### Database Strategy: Separate Databases with API Integration

The Staff Service uses separate databases from other apps but integrates via REST API. This allows:

- Independent deployment and scaling
- Clear data ownership boundaries
- Flexible integration patterns
- Graceful degradation when Staff Service is unavailable

### Data Flow Patterns

#### Pattern 1: Standalone Digital ID

- Digital ID uses its own `employees` table
- Staff created directly in Digital ID
- No integration with Staff Service
- Suitable for organisations using only Digital ID

#### Pattern 2: Digital ID + Staff Service (Preferred)

- Staff Service is source of truth for staff profiles
- Digital ID reads staff data via API from Staff Service
- Digital ID maintains `employees` table with reference to Staff Service person ID
- Digital ID syncs staff data periodically or on-demand
- Suitable for organisations with multiple apps

#### Pattern 3: Staff Service → External Systems

- Staff Service exposes API/webhooks for external systems
- Entra/Microsoft 365 syncs user data from Staff Service
- HR/Finance/LMS systems receive staff updates
- Recruitment system pushes new hires to Staff Service
- Single point of integration for external systems

## Key Design Decisions

### 1. Staff Service as Source of Truth

- Staff Service `people` table is authoritative for staff information
- Other apps maintain references (person_id) but not full copies
- Staff Service provides read-only API for staff data
- Write operations only in Staff Service (with exceptions for specific app data)

### 2. Optional Integration

- Apps check for Staff Service availability at startup
- If unavailable, apps operate standalone
- Configuration flag: `USE_STAFF_SERVICE=true/false`
- Graceful degradation - apps work without Staff Service

### 3. Data Synchronisation

- Pull model: Apps fetch from Staff Service API
- Push model: Staff Service sends webhooks on updates
- Hybrid: Periodic sync + webhooks for real-time
- Cache locally with TTL for performance

### 4. External System Integration

- Staff Service exposes REST API
- Support for webhooks/subscriptions
- Batch sync endpoints for bulk operations
- Authentication via API keys per system
- Rate limiting and quotas

### 5. Recruitment System Input

- Staff Service provides import endpoint
- CSV/JSON import formats
- Validation and error handling
- Audit trail for imports
- Manual review workflow option

## Benefits of This Architecture

1. **Flexibility**: Apps can work standalone or integrated
2. **Centralised Management**: Single source of truth for staff data
3. **Pre-populated Forms**: Other apps can pre-fill forms with staff data
4. **Monitoring & Reporting**: Centralised staff data enables cross-app reporting
5. **External Integration**: Single point for HR, Finance, LMS, Entra integration
6. **Scalability**: Add new apps without changing Staff Service core
7. **Data Consistency**: Staff Service ensures data quality and validation

## Migration Strategy

### For Organisations with Existing Digital ID

1. Deploy Staff Service
2. Export employees from Digital ID
3. Import to Staff Service via API
4. Configure Digital ID to use Staff Service
5. Sync existing data
6. Gradually migrate staff management to Staff Service

### For New Organisations

1. Deploy Staff Service first
2. Import staff from recruitment system
3. Deploy other apps (Digital ID, Medication, etc.)
4. Configure apps to use Staff Service API
5. Staff Service becomes central hub from start

## Related Documentation

- [Integration Patterns](INTEGRATION.md) - How to integrate with Staff Service
- [API Reference](API.md) - Complete API documentation
- [External Systems](EXTERNAL_SYSTEMS.md) - External system integration guide

