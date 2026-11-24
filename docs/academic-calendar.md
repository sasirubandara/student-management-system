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