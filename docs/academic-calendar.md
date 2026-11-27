# Academic Year and Term Management

## Overview

The Student Management System provides an academic-calendar management module for defining academic years and the terms within them.

The module supports academic years spanning multiple calendar years and enforces date consistency.

## Features

- Create, view, search, and update academic years.
- Create and update academic terms within registered academic years.
- Display terms in their configured sequence.
- Prevent overlapping academic-year date ranges.
- Prevent overlapping terms within an academic year.
- Prevent terms from extending beyond their parent academic year.
- Prevent changes to academic-year dates that would exclude existing terms.
- Enforce unique academic-year identifiers.
- Enforce unique term numbers within each academic year.
- Record successful calendar changes in the audit log.
- Provide role-based authorization for administrators and staff.

## Permissions

| Operation | Administrator | Staff |
|---|---|---|
| View academic years | Allowed | Allowed |
| View academic terms | Allowed | Allowed |
| Create academic years | Allowed | Denied |
| Update academic years | Allowed | Denied |
| Create academic terms | Allowed | Denied |
| Update academic terms | Allowed | Denied |
| Delete academic years or terms | Not supported | Not supported |

These permissions apply to active accounts.

## Data Model

### Academic Year

The `academic_years` table contains:

- Numeric academic-year identifier
- Name
- Start date
- End date
- Creation and update timestamps

### Academic Term

The `academic_terms` table contains:

- Parent academic-year identifier
- Name
- Term number
- Start date
- End date
- Creation and update timestamps

Each academic term belongs to one academic year.

## Business Rules

Academic years must have valid date ranges and cannot overlap other registered academic years.

Terms must remain within their parent academic-year boundaries and cannot overlap other terms belonging to the same year.

Term numbers must be unique within an academic year.

The application validates these rules at the service layer, including when operations are performed outside HTTP controllers.

## Concurrency Protection

Academic-calendar write services use a shared transaction coordinator.

The coordinator acquires a database row lock before performing validation and updates.

This serializes cooperating calendar writes on MySQL InnoDB.

The calendar record and its audit event are written within the same transaction.

Isolated MySQL experiments have been used to exercise competing academic-year and academic-term creation. These tests cover the observed scenarios, not every possible concurrent transaction schedule.

## Application Settings Compatibility

The existing `current_academic_year` setting remains a numeric identifier.

Registered academic years appear as suggestions in Application Settings.

Academic classes continue using their existing numeric academic-year fields.

Creating or updating an academic-calendar record does not automatically modify the current-academic-year setting or existing academic-class records.

Calendar registration is not currently mandatory for setting the institution's current academic year.

## Academic Class Integration

Academic Class Management integrates with registered academic-calendar years while preserving the existing numeric `academic_year` column.

### Academic-Year Selection

- New academic classes must reference a registered academic year.
- Academic-class creation forms display registered academic years as selectable options.
- The configured current academic year is selected by default when it is registered.
- When no academic years are registered, the creation form displays guidance instead of offering invalid years.
- Administrators can change an existing class to another registered academic year.

### Historical Compatibility

- Existing academic classes retain their original numeric academic-year values.
- Academic classes referencing unregistered historical years remain accessible.
- Administrators can update unrelated class information without changing a historical unregistered year.
- Changing an existing class to a different year requires that the destination year be registered.
- Existing enrollments, teaching assignments, attendance records, examinations, and academic reports retain their current relationships.

### Calendar Information

Academic-class details display registered calendar information when a matching academic year exists, including the calendar name and date range.

Classes with unregistered academic years display their existing numeric value and an explanatory registration status.

### Data Model

The integration matches `academic_classes.academic_year` with `academic_years.year`.

No additional foreign-key column or database migration is required.

This is an application-level association rather than a database-enforced foreign key.

### Authorization and Audit Logging

Existing academic-class authorization policies remain unchanged.

Administrators can create and update academic classes, while staff members retain read-only access.

Successful academic-class changes continue to use transactional audit logging. Updates that do not change any fields do not generate additional audit events.

### Testing

Dedicated integration tests verify registered-year selection, rejection of unregistered destination years, historical compatibility, Application Settings defaults, calendar information display, and audit integrity.

## Audit Events

The module records the following events:

- `academic_year.created`
- `academic_year.updated`
- `academic_term.created`
- `academic_term.updated`

Audit records identify the affected model and the changed fields.

Unchanged updates do not produce new update audit events.

## Testing

Automated Pest tests cover database structure, validation, authorization, HTTP endpoints, audit integrity, rollback behavior, settings integration, and backward compatibility.

MySQL concurrency verification is performed separately using an isolated database because SQLite in-memory testing does not reproduce MySQL's row-locking behavior.

## Limitations

- Academic years and terms cannot currently be deleted through the interface.
- Existing academic-class records are not linked to calendar records through foreign keys.
- The current academic-year setting can reference an unregistered numeric year.
- Concurrency behavior has been verified for selected scenarios only.

## Future Enhancements

Potential enhancements include stricter calendar selection, academic-year activation workflows, enhanced calendar visualization, additional automated MySQL integration tests, and deeper integration with enrollment and examination scheduling.