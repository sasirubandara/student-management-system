# Student Management System

[![Laravel CI](https://github.com/sasirubandara/student-management-system/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/sasirubandara/student-management-system/actions/workflows/ci.yml)

A web-based Student Management System built with PHP and Laravel. The application provides student and teacher management, academic year and term management, academic class and subject management, class-subject assignments, teacher-class-subject assignments, student enrollment management, student promotion and academic progression, academic timetable and scheduling management, subject-based attendance management, examination and grade management, academic reports, student CSV import and export, student document management, in-app notifications, audit logs and activity history, authentication, role-based authorization, administrator-controlled user account management, advanced search and filtering, dashboard analytics, and centralized application settings and configuration management.

This project is developed incrementally as a learning and software development portfolio project, with an emphasis on maintainable code, application security, automated testing, and professional Git workflows.

## Features

### Student Management

- Register new students.
- View all registered students.
- View individual student profiles.
- Edit student information.
- Search students by student number, first name, last name, and email.
- Paginate student records.
- Archive students using soft deletes.
- View archived student records.
- Restore archived students.
- Maintain student registration dates and record statuses.

### Student CSV Import and Export

- Import multiple student records using CSV files.
- Download a blank CSV template containing the required and optional columns.
- Validate CSV headers and individual student records before importing.
- Report invalid rows and duplicate student numbers.
- Continue importing valid records when other rows contain errors.
- Default student status to Active when the status column is omitted or empty.
- Prevent duplicate student numbers, including those belonging to archived students.
- Export active student records as CSV files.
- Export student records using the current search criteria.
- Protect exported CSV values against spreadsheet formula injection.
- Apply existing student authorization policies to import, template download, and export operations.
- Display import summaries and row-level error messages.
- Reuse the existing student database table without introducing additional database tables.

**CSV column requirements**

Required columns:

- `student_number`
- `first_name`
- `last_name`
- `enrollment_date`

Optional columns:

- `email`
- `phone`
- `date_of_birth`
- `status`

CSV uploads are limited to 2 MB.

### Student Document Management

- Upload documents and associate them with individual student records.
- Store document titles, categories, descriptions, original filenames, MIME types, file sizes, and upload dates.
- Record the system user responsible for each document upload.
- Support PDF, JPG, JPEG, and PNG document formats.
- Restrict document uploads to a maximum file size of 5 MB.
- Generate unique filenames for uploaded documents.
- Store uploaded documents in a dedicated private storage disk.
- Display uploaded documents on individual student document management pages.
- Download documents through authenticated application routes.
- Verify student ownership before allowing document downloads or deletion.
- Allow administrators and staff to view, upload, and download documents according to student permissions.
- Restrict document deletion to administrators.
- Preserve document metadata when an uploader's account is deleted.
- Prevent accidental permanent deletion of students who still have associated documents.

**Document storage and security**

Uploaded files are stored privately under `storage/app/private/student-documents`, rather than being exposed through public URLs.

Document downloads are handled through authenticated routes with authorization and student ownership checks. Generated filenames prevent uploaded filenames from determining storage paths.

The `student_documents` database table stores document metadata and maintains relationships with students and the users who uploaded the documents.

### Student Enrollment Management

- Connect students with academic classes through enrollment records.
- View enrollment listings and individual enrollment details.
- Search and paginate enrollment records.
- Create new student enrollments.
- Update enrollment dates, statuses, and administrative notes.
- Support three enrollment statuses: **Active**, **Completed**, and **Withdrawn**.
- Prevent duplicate enrollment records for the same student and academic class.
- Reject creating or reactivating a second Active enrollment for the same student through supported enrollment-management workflows.
- Reject enrollment creation when the selected student or academic class is inactive or archived.
- Coordinate enrollment writes with promotions using a transaction and a student-row lock.
- Protect promotion-linked enrollment dates and keep a promoted source enrollment Completed; permit administrative notes to be updated.
- Preserve enrollment records as historical records rather than permanently deleting them.

**Enrollment history and duplicate prevention**

Each student and academic class combination can have only one enrollment record. A unique database constraint prevents another record from being created for the same combination, even after an enrollment has been marked Completed or Withdrawn. Existing records can instead be updated, subject to promotion-history safeguards.

The application also validates that a student does not have multiple Active enrollments. Manual enrollment creation and updates lock the student row before checking this condition. **The one-Active-enrollment rule is enforced through participating application write paths, not a database-level unique constraint on Active status**; independent writers must follow the same locking protocol. Historical Completed and Withdrawn enrollments are retained.

Enrollment records do not use soft deletes. Database relationships protect enrollment records against accidental deletion of related student or academic class records.

### Student Promotion and Academic Progression

- Promote a student from an Active source enrollment into a separate Active destination enrollment in a later academic year.
- Support **Advanced** progression to the next grade and **Repeated** progression to the same grade in a later academic year.
- Require the destination class to be active, not archived, and associated with a registered academic year.
- Validate the source student, source enrollment and class, destination grade and year, and promotion date against the destination academic-year boundaries.
- Reject attempts to promote an enrollment twice, reuse a destination class already enrolled by the student, or promote a student with conflicting Active enrollments.
- Complete the source enrollment without altering its original class or enrollment date; create a new Active destination enrollment.
- Preserve attendance records, examination results, and academic reports linked to the original enrollment instead of moving them to the destination.
- Display a promotion history directory, a promotion creation form, and individual progression details.
- Allow active administrators to create and view promotions; allow active staff to view promotion history but not create or modify promotions.
- Record each successful promotion as a `student.promotion.created` audit event in the same database transaction as its enrollment changes.

**Promotion history and database relationships**

The `student_promotions` table links each student to one source enrollment and one destination enrollment, with the progression type (`advanced` or `repeated`), promotion date, optional notes, and the administrator who performed the action. Unique constraints prevent a source or destination enrollment from being reused in multiple promotion records. Foreign keys restrict deletion of referenced students and enrollments; if the administrator account is deleted, `promoted_by` may become null without deleting the history.

**Enrollment and transaction integrity**

Promotion writes lock the student row, reload and lock the source enrollment, validate business rules, mark the source Completed, create the destination Active, and record the promotion and audit event inside one transaction. Supported manual enrollment writes use the same student-level locking point to reduce conflicting Active enrollments. Ordinary enrollment editing cannot change promotion-linked enrollment dates or change an already-promoted source from Completed to another status; notes remain editable.

Concurrent MySQL tests on a **separate local InnoDB database** exercised two competing promotions and a promotion competing with manual enrollment creation. In the observed runs, only one promotion succeeded, the competing operation was rejected, and the final enrollment and audit counts were consistent. These checks supplement the automated SQLite feature suite; they do not prove exhaustive concurrency safety, and other writers that bypass the locking protocol could violate the application-level one-Active-enrollment rule.

### Teacher Management

- Register new teachers.
- View the teacher directory.
- View individual teacher profiles.
- Edit teacher information.
- Search teachers by teacher number, first name, last name, and email.
- Paginate teacher records.
- Maintain teacher employment dates and employment statuses.
- Support Active and Inactive teacher statuses.
- Archive teachers using soft deletes.
- View archived teacher records.
- Restore archived teachers.

Teacher records are maintained separately from system user accounts. Teacher profiles contain personal information, contact information, and employment information.

### Academic Year and Term Management

The academic-calendar module registers academic years and their terms, with validation of inclusive date ranges and role-based access.

**Academic years**

- Create, list, search, view, and update academic years with numeric year identifiers, names, and start/end dates.
- Support academic years that span two calendar years.
- Reject duplicate year identifiers, invalid date ranges, and overlapping academic-year periods.
- Prevent changes to an academic year's boundaries that would place existing terms outside that year.

**Academic terms**

- Create and update named, numbered terms belonging to registered academic years.
- View terms associated with an academic year in term-number order.
- Reject duplicate term numbers within the same year, date ranges outside the parent year, and overlapping terms within that year.
- Allow consecutive terms without overlapping dates.

**Permissions, integrity, and compatibility**

- Active administrators can view, create, and update academic years and terms; active staff can view them but cannot modify them.
- Enforce date and overlap rules through dedicated services, including requests that do not originate from an HTTP controller.
- Use transactions and a shared InnoDB row lock to coordinate calendar writes performed through the application services.
- Write successful changes and their audit events in the same database transaction. Unchanged updates do not create new update audit events.
- Keep the existing numeric academic-year field on academic classes and the numeric `current_academic_year` application setting; calendar registration does not automatically migrate old class records.
- Offer registered years as suggestions in the administrator Settings interface without requiring every configured numeric year to have a calendar record.

Deleting academic years and terms is not supported through the application interface in this feature. The concurrency mechanism has been verified against selected overlapping-write scenarios on an isolated MySQL database; this is not a claim of exhaustive concurrency testing.

For detailed business rules, implementation notes, and limitations, see [Academic Year and Term Management](docs/academic-calendar.md).

### Academic Class Management

- Create and manage academic classes.
- Store class names, codes, grade levels, and numeric academic years.
- View individual academic class details.
- Search classes by name and class code.
- Paginate academic class records.
- Update academic class information.
- Archive academic classes using soft deletes.

**Academic Calendar Integration**

- Choose a registered academic year when creating a class or changing an existing class's year.
- List registered years in the class creation and editing forms, showing their year identifiers and names.
- Preselect the institution's configured current academic year in the creation form when that year has been registered; otherwise, require an explicit valid selection.
- Display guidance and a link to academic-year registration when no calendar years exist.
- Preserve existing classes whose numeric academic year has no matching calendar record. Administrators can edit unrelated class details without changing the historical year.
- Reject attempts to change a class's year to an unregistered destination year.
- Show calendar registration status, name, and start/end dates on class details pages when a registered year matches; display an explanatory historical status otherwise.
- Keep staff read-only and retain existing academic-class authorization, soft deletes, and transactional change-field audit logging.

The integration matches `academic_classes.academic_year` to `academic_years.year` at the application level. It does **not** add a foreign key, migrate existing class records, or automatically change historical academic-year values. The existing `current_academic_year` setting remains numeric; it may reference a year that is not registered, but new class creation requires a registered year.

See [Academic Calendar Documentation](docs/academic-calendar.md) for the calendar and academic-class integration rules.

### Subject Management

- Create and manage academic subjects.
- Store subject names, unique subject codes, and descriptions.
- View individual subject details.
- Search subjects by name and subject code.
- Paginate subject records.
- Update subject information.
- Archive subjects using soft deletes.

### Class-Subject Assignment

- Assign multiple subjects to an academic class.
- Assign the same subject to multiple academic classes.
- Display assigned subjects on academic class details pages.
- Allow administrators to assign and remove subjects.
- Allow administrators and staff to view subject assignments.
- Prevent duplicate subject assignments using validation and a database unique constraint.
- Prevent inactive or archived subjects from being newly assigned.
- Preserve subject assignment records when related subjects or classes are archived.

**Class-subject relationship**

Academic classes and subjects use a many-to-many relationship through the `academic_class_subject` pivot table.

Each assignment connects one academic class with one subject. A unique database constraint prevents duplicate assignments for the same class and subject.

### Teacher-Class-Subject Assignment

- Assign teachers to subjects within academic classes.
- Allow teachers to teach multiple subjects and academic classes.
- Allow multiple teachers to teach the same subject in the same academic class.
- View teaching assignment listings and individual assignment details.
- Search teaching assignments by teacher, academic class, and subject information.
- Paginate teaching assignment records.
- Allow administrators to create, update, and remove teaching assignments.
- Allow administrators and staff to view teaching assignments.
- Prevent duplicate assignments for the same teacher, academic class, and subject.
- Require the subject to already be assigned to the selected academic class.
- Reject inactive or archived teachers, academic classes, and subjects when creating or updating assignments.

**Teaching assignment relationship and duplicate prevention**

Each teaching assignment connects one teacher, one academic class, and one subject through the `teaching_assignments` table.

The table uses foreign keys to maintain relationships with teachers, academic classes, and subjects. A composite unique database constraint prevents the same teacher from being assigned to the same subject in the same academic class more than once.

Different teachers can be assigned to the same class and subject combination. A teacher can also be assigned to different classes and subjects.

Teaching assignments do not use soft deletes. Administrators can remove an assignment, while foreign-key restrictions protect related teacher, academic class, and subject records from accidental deletion.

### Academic Timetable and Scheduling Management

The timetable module organizes weekly lessons by **academic term**, teaching assignment, weekday, and start/end time. A teaching assignment associates a teacher, academic class, and subject; each timetable entry references that assignment and a registered term.

**Scheduling and weekly views**

- List timetable entries with optional academic-term, class, and teacher filters.
- Allow administrators to create, view, edit, and delete lessons through authenticated timetable pages.
- Allow active administrators and staff to view timetable entries and weekly schedules for an academic class or teacher.
- Show lessons grouped by weekday, with lesson times and the related teacher, class, and subject details.
- Require an explicit academic term for weekly displays. Class timetable selectors show only terms associated with the class's numeric academic year; a manually supplied term from another year is rejected.
- Allow teacher timetables to be filtered by any registered term, including terms from different academic years.

**Validation and conflict protection**

- Validate weekday and time ranges, registered academic terms, and eligible teaching assignments.
- Require the teaching assignment's class year to match the academic year associated with the selected term.
- Reject schedule changes involving inactive or archived teachers, classes, or subjects, or subjects not assigned to the relevant class.
- Reject overlapping lessons for the **same academic class** or **same teacher** within one term and weekday, including partially overlapping periods. Adjacent, non-overlapping periods remain eligible.
- Prevent scheduled records from being invalidated through participating teacher, class, subject, class-subject, and teaching-assignment management workflows. In particular, changes that would deactivate, archive, detach, or reassign referenced entities are rejected while timetable entries depend on them.
- Prevent renumbering an academic year referenced by academic classes or scheduled lessons, while permitting eligible changes to other year metadata.

**Transactions, storage, and audit history**

The `timetable_entries` table references academic terms and teaching assignments and records the weekday, time period, and creating user. A dedicated `timetable_locks` table provides a singleton row for serialization of participating timetable writes. Scheduling operations use a database transaction and a shared row lock to check conflicts and apply changes atomically. Related academic-calendar writes use a consistent lock ordering when both calendar and timetable records are involved.

Successful timetable changes are recorded as `timetable.entry.created`, `timetable.entry.updated`, and `timetable.entry.deleted` audit events. Audits participate in the write transaction, so rolled-back changes do not leave successful-change audit events.

**Concurrency testing and limits**

On an isolated local **MySQL 8.0 / InnoDB** database, independent PHP workers verified shared-row lock contention, competing creations for the same schedule slot, overlapping teacher lessons across different classes, scheduling competing with teacher deactivation, and class creation competing with academic-year renumbering. In the observed runs, invalid simultaneous outcomes were rejected and the committed records and audit counts remained consistent. These targeted experiments supplement the automated SQLite suite; they do **not** prove all possible orderings or protect direct database writers that bypass the participating application transaction protocol. The temporary test scripts and local database credentials are not part of the repository.

### Attendance Management

- Create subject-based attendance sessions linked to teaching assignments.
- Record attendance for students with active enrollments in the assigned academic class.
- Support four attendance statuses: **Present**, **Absent**, **Late**, and **Excused**.
- Record attendance for multiple students in one submission.
- Update previously recorded student attendance.
- Add optional remarks to individual attendance records.
- View attendance session details and attendance summaries.
- Search attendance sessions by teacher, academic class, subject, and date.
- Paginate attendance session records.
- Prevent duplicate sessions for the same teaching assignment and date.
- Prevent duplicate student attendance records within a session.
- Reject attendance records for students enrolled in a different academic class.
- Reject attendance recording for inactive or archived students.
- Reject attendance recording for completed or withdrawn enrollments.
- Preserve historical attendance records for inactive or archived students.
- Allow administrators and staff to view, create, and update attendance.
- Restrict removal of attendance sessions and individual attendance records to administrators.
- Prevent removal of attendance sessions that contain student attendance records.

**Attendance session and student attendance relationships**

Each attendance session belongs to one teaching assignment and has an attendance date. A unique database constraint prevents more than one session from being created for the same teaching assignment on the same date.

Individual student attendance records connect an attendance session to a student enrollment. A student can have only one attendance record per session.

Attendance records retain their relationships with student enrollments and attendance sessions. Foreign-key restrictions help protect historical attendance records against accidental deletion of related records.

Each attendance session also records the user who created it.

### Examination and Grade Management

- Create examinations linked to teaching assignments.
- Support multiple examination types: Quiz, Assignment, Midterm, Term Examination, Final Examination, Practical, and Other.
- Set examination dates, maximum marks, and optional pass marks.
- Add optional examination descriptions.
- View examination listings and individual examination details.
- Search examinations by title, teacher, academic class, and subject.
- Paginate examination records.
- Allow administrators and staff to create and update examinations.
- Prevent duplicate examinations for the same teaching assignment, title, and date.
- Record examination results for multiple students in one submission.
- Update previously recorded examination results without creating duplicates.
- Associate examination results with student enrollment records.
- Automatically calculate grades using a configurable grading scale.
- Calculate percentages based on marks obtained and maximum marks.
- Validate examination marks to prevent negative values or marks exceeding the maximum.
- Validate optional pass marks against maximum marks.
- Reject results for students outside the examination's assigned academic class.
- Reject new results for inactive or archived students.
- Reject new results for completed or withdrawn enrollments.
- Preserve historical examination results when students are archived or enrollments become inactive.
- Prevent changing an examination's teaching assignment after results have been recorded.
- Prevent reducing maximum marks below an already recorded result.
- Recalculate existing grades when maximum marks are changed.
- Restrict examination deletion and individual result deletion to administrators.
- Prevent examination deletion when examination results exist.

**Examination and result relationships**

Each examination belongs to one teaching assignment, connecting a teacher, an academic class, and a subject.

Each examination result connects an examination to a student enrollment. A unique database constraint prevents duplicate results for the same examination and enrollment.

The application calculates grades on the server using the grading scale configured in `config/examinations.php`. Grades are determined from the percentage of marks obtained relative to the examination's maximum marks.

Foreign-key restrictions protect examinations, student enrollments, and historical examination results against accidental deletion of related records.

### Academic Reports

- Provide a searchable directory of student academic reports.
- Display individual student information and complete enrollment history, including active, completed, and withdrawn enrollments.
- Summarize each student's attendance records by status and display attendance history with class, subject, teacher, date, and remarks.
- Display examination history, marks obtained, maximum marks, grades, and remarks for each student.
- Provide attendance reports filterable by academic class, subject, and date range.
- Summarize attendance records by **Present**, **Absent**, **Late**, and **Excused** across all matching sessions.
- Provide examination reports filterable by academic class, subject, and date range.
- Summarize examination counts, recorded results, and grade distributions across matching examinations.
- Allow administrators to view archived students in the report directory and open their historical academic reports; staff users can view active student reports only.
- Provide browser-based print layouts for student, attendance, and examination reports.
- Download student academic reports as **PDF** or **Excel (XLSX)** files, including enrollment history, attendance, examination results, and summaries.
- Download attendance reports as **PDF** or **XLSX**, filtered by academic class, subject, and inclusive date range.
- Download examination reports as **PDF** or **XLSX**, using the same class, subject, and date filters.
- Display human-readable class and subject names in filtered PDF and XLSX reports instead of database IDs.
- Include all matching records in downloaded reports rather than limiting exports to the current browser pagination page.
- Generate downloadable reports even when no records match the filters, with informative empty states.
- Restrict archived student report downloads to administrators; staff can export permitted active student reports.
- Record successful exports in the audit log, including the report category and file format (`pdf` or `xlsx`).
- Treat untrusted text in XLSX exports as strings to prevent spreadsheet formula interpretation.
- Keep reports read-only and reuse existing academic records without introducing additional database tables.

Reports preserve historical context by displaying related archived classes, subjects, and teachers where those records are available. Attendance and examination report summaries are calculated across all records matching the selected filters, not only the current pagination page.

**Report download formats**

| Report | PDF | Excel (XLSX) | Supported filters |
|---|:---:|:---:|---|
| Individual student academic report | Yes | Yes | Student record; archived access is administrator-only |
| Attendance report | Yes | Yes | Academic class, subject, start date, end date |
| Examination report | Yes | Yes | Academic class, subject, start date, end date |

Exports use the same report-selection and validation services as the browser reports. An end date earlier than the start date is rejected. Successful PDF/XLSX generation is audited; an audit entry indicates a generated response, not confirmation that a user completed the download on their device.

### Dashboard and Statistics

- Provide a central dashboard for administrators and staff after authentication.
- Display summary cards for students, teachers, academic classes, subjects, student enrollments, teaching assignments, attendance sessions this month, and examinations.
- Show student and teacher totals with active and inactive counts.
- Summarize student enrollments by status: **Active**, **Completed**, and **Withdrawn**.
- Summarize attendance records by status: **Present**, **Absent**, **Late**, and **Excused**.
- Display upcoming examinations with their dates and teaching assignment details.
- Display recent student records, student enrollments, and attendance sessions.
- Show archived student and teacher counts to administrators only.
- Keep dashboard statistics based on the application's existing academic and attendance records without introducing additional database tables.

The dashboard provides administrators and staff with an overview of current academic activity. Archived student and teacher statistics are restricted to administrators in accordance with the application's role permissions.

### Advanced Search and Filtering

- Provide search and filtering controls across supported management listings.
- Combine search criteria with available filters to narrow matching records.
- Preserve selected filters during pagination.
- Respect existing authorization rules when retrieving filtered records.

### Dashboard Analytics

- Display overall attendance rates, examination pass rates, and average examination scores.
- Calculate attendance rates using present and late records, excluding excused absences.
- Calculate examination pass rates across graded results and average scores using percentages.
- Display attendance and enrollment status distributions.
- Handle empty datasets without misleading statistics.
- Retain existing dashboard summaries and administrator-only archived record counts.

### In-App Notifications

The application provides database-backed notifications to keep active users informed about important system activities.

**Supported events**

| Event | Activity |
|---|---|
| `student.created` | A new student is registered. |
| `enrollment.created` | A student is enrolled in an academic class. |
| `attendance.session_created` | An attendance session is created. |
| `examination.created` | An examination is scheduled. |
| `student.document_uploaded` | A document is uploaded to a student record. |

**Notification features**

- Notify other active users when supported activities occur; the acting user is excluded.
- Display a responsive Notification Center with paginated notification history and read/unread status indicators.
- Filter notifications using All, Unread, and Read views.
- Display unread and matching notification counts.
- Preserve selected filters during pagination and after read-status updates.
- Display relative timestamps and absolute timestamps using configured timezone and date-format preferences.
- Provide helpful empty states and keyboard-accessible controls.
- Show an unread notification count in the authenticated navigation.
- Allow users to mark one notification or all their unread notifications as read.
- Store notification event types, titles, messages, icons, and optional destination URLs in the database.
- Restrict student document upload notifications to active administrators and staff.

**Security and reliability**

Notification routes require authentication and an active account. Users can view and update only notifications belonging to their own account. Student document downloads remain protected by student authorization and private file storage. Notification records are created within the relevant activity's database transaction; document upload processing also attempts to clean up the stored file if the database operation fails.

The notifications table is created by the standard `php artisan migrate` command in the installation instructions. No separate notification service or queue worker is required for the current synchronous database delivery.

### Application Settings and Configuration Management

Active administrators can manage institution-wide preferences through the **Settings** page in the authenticated navigation.

**Supported settings**

| Setting | Description |
|---|---|
| Institution name | Configures authenticated navigation branding. |
| Institution email | Optional institutional contact email. |
| Institution phone | Optional contact number stored as a string. |
| Institution address | Optional institutional address. |
| Current academic year | Numeric preferred default for academic-class creation (2000–2100); registered calendar years are suggested. A class can only be created with a registered year. |
| Timezone | Preferred timezone for notification timestamp display. |
| Date format | Preferred date format for notification timestamps. |

**Storage, reliability and integration**

- Store settings in the `application_settings` table using a designated record with ID `1`.
- Automatically initialize default settings on first access.
- Retrieve supported settings through `ApplicationSettingsService` with cached array values and a one-hour cache lifetime.
- Invalidate cached settings after successful updates, with additional post-commit invalidation when an outer transaction is active.
- Validate settings through `UpdateApplicationSettingRequest` and enforce active-administrator permissions through `ApplicationSettingPolicy`.
- Use database transactions and record locking for settings updates and their audit records.
- Record changed settings with their previous and new values; unchanged submissions do not generate settings update audit events.
- Preserve existing academic-class year values when the configured default changes.
- Suggest registered academic calendar years in the settings form without requiring a matching calendar record or changing the existing numeric setting. The class creation form uses the configured value only when it is registered.
- Format dates and timestamps through the reusable `DateFormattingService` without changing Laravel's global timezone or stored UTC timestamps.
- Provide a responsive settings form with accessible validation messages and keyboard focus indicators.

Supported date formats are `Y-m-d`, `d/m/Y`, `m/d/Y`, `d M Y`, and `M d, Y`. Institution contact details are stored for future report and document integrations. Localization currently applies to notification timestamps, not every date in the application.

Infrastructure configuration, database credentials, and application encryption keys remain outside the settings module and must be managed through environment configuration. Cache invalidation is designed for the current application workflow; strict distributed cache consistency is not guaranteed.

### Audit Logs and Activity History

The system records important application activities to provide an audit history of actions performed by administrators and staff users.

**Recorded activities**

Audit events include:

- Authentication activities, including successful login and logout.
- User account creation, role changes, and activation status changes.
- Student creation, updates, archiving, and restoration.
- Teacher creation, updates, archiving, and restoration.
- Student enrollment creation and updates.
- Student promotion and academic progression (`student.promotion.created`).
- Attendance session creation and deletion.
- Student attendance record creation, updates, and deletion.
- Examination creation, updates, and deletion.
- Examination result creation, updates, and deletion.
- Academic class creation, updates, and archiving.
- Academic year and term creation and updates (`academic_year.created`, `academic_year.updated`, `academic_term.created`, and `academic_term.updated`).
- Subject creation, updates, and archiving.
- Academic class subject assignments and removals.
- Teaching assignment creation, updates, and deletion.
- Timetable entry creation, updates, and deletion (`timetable.entry.created`, `timetable.entry.updated`, and `timetable.entry.deleted`).
- Student CSV import processing.
- Student CSV export requests.
- Student CSV template downloads.
- Student document uploads and deletions.
- Application settings updates (`application_settings.updated`).
- Academic report exports (`reports.student.exported`, `reports.attendance.exported`, and `reports.examinations.exported`), recording the export format and report type.

**Audit log management**

- Administrators can view the audit log directory.
- Administrators can search and filter audit records.
- Administrators can filter records by event, module, user, and date.
- Administrators can view individual audit activity details.
- Staff users cannot access the audit log interface.
- Audit records are read-only through the application interface.
- The application does not provide routes for modifying or deleting audit records.

**Privacy and security**

Audit logging is designed to avoid unnecessarily storing sensitive information.

- Passwords, tokens, secrets, authorization data, and similar credentials are excluded from audit properties.
- Student and teacher update events record changed field names rather than personal field values.
- Enrollment notes and attendance remarks are not stored in audit properties.
- Student document events exclude file paths, original filenames, descriptions, and file contents.
- CSV export events record whether a search filter was applied without storing the actual search term.
- Document metadata recorded in audit events is limited to relevant information such as student ID, category, MIME type, and file size.
- Settings update events record previous and new values of changed supported settings, subject to the audit logger’s sanitization and string-length limits. Institution contact information may therefore appear in administrator-only audit records.

Audit entries retain their activity history when an associated user account or audited record is deleted, subject to the application's database relationships.

### Authentication and Authorization

- Secure user login and logout.
- Login rate limiting.
- Password hashing.
- Session regeneration after successful authentication.
- Role-based access control.
- Two user roles: Administrator and Staff.
- Authorization enforced through Laravel policies and Gates.

### User Management

Administrators can:

- View registered system users.
- Search users by name and email.
- Browse users with pagination.
- Create staff accounts.
- Promote staff members to administrators.
- Change administrator accounts to staff when safeguards allow.
- Activate and deactivate user accounts.
- View account roles and activation statuses.

### Account Security

- Inactive users cannot log in.
- Existing sessions are terminated when an inactive user makes another protected request.
- Staff members cannot access administrator-only user management features.
- Administrators cannot deactivate their own accounts.
- The application protects against demoting or deactivating the last active administrator.
- Role and account-status changes use database transactions and record locking.

## Role Permissions

| Feature | Administrator | Staff |
|---|:---:|:---:|
| View student records | Yes | Yes |
| Register students | Yes | Yes |
| View student profiles | Yes | Yes |
| Edit student records | Yes | Yes |
| Archive students | Yes | No |
| View archived students | Yes | No |
| Restore archived students | Yes | No |
| Import student records | Yes | Yes |
| Export active student records | Yes | Yes |
| Download student CSV template | Yes | Yes |
| View student documents | Yes | Yes |
| Upload student documents | Yes | Yes |
| Download student documents | Yes | Yes |
| Delete student documents | Yes | No |
| View teacher directory | Yes | Yes |
| View teacher profiles | Yes | Yes |
| Register teachers | Yes | No |
| Edit teacher records | Yes | No |
| Archive teachers | Yes | No |
| View archived teachers | Yes | No |
| Restore archived teachers | Yes | No |
| View enrollment records | Yes | Yes |
| Create student enrollments | Yes | No |
| Update enrollment details and statuses | Yes | No |
| Permanently delete enrollment records | No | No |
| View student promotion history | Yes | Yes |
| Create student promotions and grade repetitions | Yes | No |
| Modify or delete existing promotion records through the application | No | No |
| View academic years and terms | Yes | Yes |
| Create academic years and terms | Yes | No |
| Update academic years and terms | Yes | No |
| Delete academic years and terms through the application | No | No |
| View academic classes | Yes | Yes |
| Create academic classes | Yes | No |
| Update academic classes | Yes | No |
| Archive academic classes | Yes | No |
| View subjects | Yes | Yes |
| Create subjects | Yes | No |
| Update subjects | Yes | No |
| Archive subjects | Yes | No |
| View class-subject assignments | Yes | Yes |
| Assign subjects to classes | Yes | No |
| Remove subject assignments | Yes | No |
| View teaching assignments | Yes | Yes |
| Create teaching assignments | Yes | Yes |
| Update teaching assignments | Yes | Yes |
| Remove teaching assignments | Yes | No |
| View timetable entries and class/teacher weekly timetables | Yes | Yes |
| Create timetable entries | Yes | No |
| Update timetable entries | Yes | No |
| Delete timetable entries | Yes | No |
| View attendance sessions | Yes | Yes |
| Create attendance sessions | Yes | Yes |
| View student attendance records | Yes | Yes |
| Record and update student attendance | Yes | Yes |
| Remove empty attendance sessions | Yes | No |
| Remove individual attendance records | Yes | No |
| View examinations | Yes | Yes |
| View academic reports | Yes | Yes |
| View archived student academic reports | Yes | No |
| Export active student academic reports (PDF/XLSX) | Yes | Yes |
| Export archived student academic reports (PDF/XLSX) | Yes | No |
| Export attendance and examination reports (PDF/XLSX) | Yes | Yes |
| Create examinations | Yes | Yes |
| Update examinations | Yes | Yes |
| Remove examinations | Yes | No |
| View examination results | Yes | Yes |
| Record and update examination results | Yes | Yes |
| Remove individual examination results | Yes | No |
| View own in-app notifications | Yes | Yes |
| Mark own notifications as read | Yes | Yes |
| Filter own notifications | Yes | Yes |
| Mark all own notifications as read | Yes | Yes |
| View application settings administration page | Yes | No |
| Update application settings | Yes | No |
| View audit logs | Yes | No |
| Search and filter audit logs | Yes | No |
| View individual audit activities | Yes | No |
| Modify or delete audit records through the application | No | No |
| View user directory | Yes | No |
| Create staff accounts | Yes | No |
| Manage user roles | Yes | No |
| Activate/deactivate accounts | Yes | No |

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.4 or later | Backend programming language |
| Laravel 13 | Backend web framework |
| MySQL / SQLite | Relational databases |
| Blade | Server-side templating |
| HTML5 | Web page structure |
| CSS3 | Styling |
| Tailwind CSS 4 | CSS utility framework |
| JavaScript | Frontend programming |
| Vite 8 | Frontend asset bundling |
| Pest 5 | Automated testing |
| DomPDF (`barryvdh/laravel-dompdf`) | Generate PDF academic reports |
| Laravel Excel / PhpSpreadsheet | Generate XLSX academic reports and preserve untrusted values as text |
| Git | Version control |
| GitHub | Source code hosting and collaboration |
| GitHub Actions | Continuous integration |

## Requirements

Before installing the application, ensure that the following tools are available:

- PHP 8.4 or later
- Composer 2
- Node.js 24
- npm
- Git
- SQLite, or a configured MySQL database

## Installation and Setup

### 1. Clone the repository

```bash
git clone https://github.com/sasirubandara/student-management-system.git
```

Navigate into the project:

```bash
cd student-management-system
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure the environment

Copy the example environment file.

For macOS and Linux:

```bash
cp .env.example .env
```

For Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure the database

The example environment is configured to use SQLite by default.

Create the SQLite database file.

For macOS and Linux:

```bash
touch database/database.sqlite
```

For Windows PowerShell:

```powershell
New-Item -ItemType File -Force database/database.sqlite
```

To use MySQL instead, update the database settings in `.env` with your own database name, username, and password.

Never commit your `.env` file or database credentials.

### 5. Run database migrations

```bash
php artisan migrate
```

### 6. Install frontend dependencies

```bash
npm ci
```

### 7. Build frontend assets

```bash
npm run build
```

### 8. Start the development server

```bash
php artisan serve
```

Open the application in your browser:

```text
http://127.0.0.1:8000
```

## Administrator Account Setup

An administrator account can be created using the interactive Artisan command:

```bash
php artisan app:create-admin
```

The command prompts for the administrator's name, email address, and password. Password input is hidden in the terminal.

An existing user can be promoted to administrator with:

```bash
php artisan app:promote-admin
```

The command asks for the target user's email address and confirmation before changing the role.

Use strong, unique passwords and keep account credentials private.

## Managing Application Settings

After running the migrations and creating an administrator account, log in and select **Settings** or visit `/settings`.

1. Enter the institution name and optional contact details.
2. Configure the current academic year, timezone, and date format. Registered academic calendar years are suggested when available, but a registered year is not required.
3. Select **Save Settings** to persist the changes.
4. Verify the updated navigation branding, new academic class defaults, and notification timestamps.

The initial settings record uses the default institution name, current calendar year, application timezone (`UTC`), and `Y-m-d` date format. No separate settings seeder is required. Staff can use the resulting application preferences but cannot access or modify the settings administration page.

## Running Tests

The project uses Pest for automated unit and feature testing.

Run the complete test suite:

```bash
php artisan test
```

Run a specific test file:

```bash
php artisan test tests/Feature/UserManagementTest.php
```

Run the student enrollment feature tests:

```bash
php artisan test tests/Feature/StudentEnrollmentManagementTest.php
```

Run the Student Promotion and Academic Progression tests:

```bash
php artisan test tests/Feature/StudentPromotionDatabaseTest.php
php artisan test tests/Feature/StudentPromotionServiceTest.php
php artisan test tests/Feature/StudentPromotionHttpTest.php
```

These tests cover progression and repetition rules, authorization, HTTP validation, enrollment-history restrictions, transactional audit rollback, and preservation of attendance and examination results.

Run the student CSV import and export feature tests:

```bash
php artisan test tests/Feature/StudentCsvImportExportTest.php
```

Run the student document management feature tests:

```bash
php artisan test tests/Feature/StudentDocumentManagementTest.php
```

Run the academic class-subject assignment feature tests:

```bash
php artisan test tests/Feature/AcademicClassSubjectAssignmentTest.php
```

Run the teacher management feature tests:

```bash
php artisan test tests/Feature/TeacherManagementTest.php
```

Run the teacher-class-subject assignment feature tests:

```bash
php artisan test tests/Feature/TeachingAssignmentManagementTest.php
```

Run the attendance management feature tests:

```bash
php artisan test tests/Feature/AttendanceManagementTest.php
```

Run the examination management feature tests:

```bash
php artisan test tests/Feature/ExaminationManagementTest.php
```

Run the academic class management and calendar integration tests:

```bash
php artisan test tests/Feature/AcademicClassManagementTest.php
php artisan test tests/Feature/AcademicClassCalendarIntegrationTest.php
php artisan test tests/Feature/AcademicClassCalendarSettingsTest.php
php artisan test tests/Feature/AcademicClassCalendarAuditTest.php
```

The integration tests cover registered-year selection and validation, historical unregistered values, the configured current-year default, class detail rendering, and audit events for changed and unchanged updates.

Run the subject management feature tests:

```bash
php artisan test tests/Feature/SubjectManagementTest.php
```

Run the in-app notification tests:

```bash
php artisan test tests/Feature/NotificationControllerTest.php
php artisan test tests/Feature/NotificationServiceTest.php
php artisan test tests/Feature/SystemActivityNotificationTest.php
```

Notification event integration tests also run within the student registration, enrollment, attendance, examination, and student document management feature tests.

Run the audit log feature tests:

```bash
php artisan test tests/Feature/AuditLogTest.php
```

Run the dashboard and statistics feature tests:

```bash
php artisan test tests/Feature/DashboardTest.php
```

Run the academic reports feature tests:

```bash
php artisan test tests/Feature/AcademicReportsTest.php
```

The academic-report test suite covers authentication, archived-student authorization, valid and invalid filters, audit metadata, empty exports, records beyond browser pagination, and XLSX formula-injection protection.

**Memory note for local testing:** PDF/XLSX generation can require more memory than the default PHP CLI limit. A temporary 512 MB CLI memory allowance can be used if necessary:

```powershell
php -d memory_limit=512M artisan test
```

The setting applies only to that PHP invocation and is **not** an established production memory requirement. On **November 28, 2026**, the full local regression suite for the Academic Timetable and Scheduling feature branch passed **756 tests and 2,742 assertions** with `php -d memory_limit=512M artisan test`. Laravel Pint passed on **225 files**; `git diff --check`, `composer validate`, `composer check-platform-reqs`, and the Vite production build also passed. The nine timetable routes were registered. These are local results from `feature/academic-timetable-scheduling`, not a claim that this feature has merged into `main`. An optional frontend optimization notice did not prevent the build. Review resource consumption with representative dataset sizes before production deployment.

Run the application settings and localization tests:

```bash
php artisan test tests/Feature/ApplicationSettingTest.php
php artisan test tests/Feature/ApplicationSettingsServiceTest.php
php artisan test tests/Feature/ApplicationSettingManagementTest.php
php artisan test tests/Feature/DateFormattingServiceTest.php
php artisan test tests/Feature/WebMiddlewareConfigurationTest.php
```

The notification controller tests also verify configured timestamp rendering. Settings tests cover authorization, validation, persistence, caching, audit logging, transaction rollback, HTML escaping, and preservation of historical academic records.

Run the academic-calendar feature tests:

```bash
php artisan test tests/Feature/AcademicCalendarAuditIntegrityTest.php
php artisan test tests/Feature/AcademicCalendarAuthorizationTest.php
php artisan test tests/Feature/AcademicCalendarBackwardCompatibilityTest.php
php artisan test tests/Feature/AcademicCalendarDatabaseTest.php
php artisan test tests/Feature/AcademicCalendarHttpValidationTest.php
php artisan test tests/Feature/AcademicCalendarManagementTest.php
php artisan test tests/Feature/AcademicCalendarNavigationTest.php
php artisan test tests/Feature/AcademicCalendarSettingsIntegrationTest.php
php artisan test tests/Feature/AcademicCalendarTransactionTest.php
php artisan test tests/Feature/AcademicTermServiceTest.php
php artisan test tests/Feature/AcademicYearServiceTest.php
```

The academic-calendar tests cover dates and overlaps, database constraints, permissions, navigation, settings compatibility, audit integrity, and transactional rollback. Separate local MySQL checks have also exercised selected academic-calendar and student-promotion concurrency scenarios with independent processes. These are targeted tests rather than exhaustive proof of behavior under all concurrent workloads; the temporary student-promotion test harness is not part of the application.

Run the academic timetable and scheduling feature tests:

```bash
php artisan test tests/Feature/TimetableEntryDatabaseTest.php
php artisan test tests/Feature/TimetableCreationHttpTest.php
php artisan test tests/Feature/TimetableDirectoryTest.php
php artisan test tests/Feature/TimetableManagementHttpTest.php
php artisan test tests/Feature/TimetableUpdateHttpTest.php
php artisan test tests/Feature/TimetableWeeklyViewsTest.php
php artisan test tests/Feature/TimetableNavigationTest.php
php artisan test tests/Feature/TimetableSchedulingServiceTest.php
php artisan test tests/Feature/TimetableAcademicYearIntegrityTest.php
php artisan test tests/Feature/TimetableCalendarCoordinationTest.php
```

The timetable tests cover lesson validation, overlapping time slots, permissions, HTTP workflows, class and teacher weekly views, academic-year integrity, and related-record safeguards. The class weekly view also rejects a registered term from another academic year. Factory-generated timetable data must use an academic term and teaching assignment whose class years agree; the default factories may create them independently, so tests requiring a valid pairing should explicitly align those relationships.

Independent-process MySQL concurrency probes were run locally against a dedicated database, separate from the SQLite unit and feature suite. Those temporary probes are not committed regression tests; repeatable MySQL integration coverage remains an area for future improvement.

Run the grade calculator unit tests:

```bash
php artisan test tests/Unit/GradeCalculatorTest.php
```

Run tests matching a particular name:

```bash
php artisan test --filter=AuthenticationTest
```

The automated test environment is configured to use an in-memory SQLite database through `phpunit.xml`. This keeps test records separate from the development database.

Run code formatting and view compilation checks:

```bash
php vendor/bin/pint --test
php artisan view:cache
php artisan view:clear
```

## Frontend Development

Start the Vite development server:

```bash
npm run dev
```

Build production frontend assets:

```bash
npm run build
```

## Continuous Integration

GitHub Actions is configured to automatically verify the application.

The Laravel CI workflow runs when:

- Code is pushed to a branch.
- A pull request targets `main`.
- The workflow is started manually.

The workflow:

1. Checks out the repository.

2. Sets up PHP and Composer.

3. Sets up Node.js.

4. Installs Composer dependencies.

5. Prepares the Laravel environment.

6. Installs npm dependencies.

7. Runs the Pest test suite.

8. Builds frontend assets using Vite.

The workflow uses SQLite for automated testing and does not require access to the local development database.

## Development Workflow

The project follows an incremental feature-based Git workflow.

1. Keep the `main` branch synchronized with GitHub.

2. Create a dedicated branch for each feature or maintenance task.

3. Implement the changes.

4. Run automated tests and frontend build checks.

5. Review and commit the changes.

6. Push the feature branch to GitHub.

7. Open a pull request targeting `main`.

8. Review and merge the pull request after verification.

Example:

```bash
git switch main
git pull --ff-only origin main
git switch -c feature/your-feature-name
```

## Project Roadmap

The following are potential future development milestones. Academic Year and Term Management, Academic Class and Calendar Integration, and Student Promotion and Academic Progression are documented as implemented features. Academic Timetable and Scheduling Management has also been implemented and locally verified on `feature/academic-timetable-scheduling`; its final commit, pull request, and merge are separate steps. Feature-branch verification does not imply that those changes are present on `main`. See [the academic-calendar documentation](docs/academic-calendar.md).

### Advanced Features

- REST API and API authentication.
- Expanded report branding and date-format integration.

### Further Quality Improvements

- Performance testing and memory optimization for large PDF/XLSX exports.
- Additional automated test coverage, including repeatable cross-process MySQL timetable concurrency tests and both lock-acquisition orderings.
- Make timetable test factories generate matching academic-term and class-year relationships by default.
- Application security improvements.
- Database backup and recovery.
- Deployment preparation.
- Performance optimization.

## Project Structure

```text
student-management-system/
├── app/
│   ├── Console/
│   ├── Exports/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Notifications/
│   ├── Models/
│   ├── Policies/
│   └── Services/
│       ├── AcademicCalendar/
│       ├── Reports/
│       ├── StudentEnrollments/
│       ├── StudentPromotions/
│       └── Timetables/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── academic-years/
│       ├── academic-terms/
│       ├── academic-classes/
│       ├── audit-logs/
│       ├── student-promotions/
│       ├── reports/
│       │   └── exports/
│       ├── notifications/
│       ├── settings/
│       ├── timetables/
│       └── students/
│           └── documents/
├── docs/
│   └── academic-calendar.md
├── routes/
├── storage/
│   └── app/
│       └── private/
│           └── student-documents/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .github/
│   └── workflows/
├── composer.json
├── package.json
└── README.md
```

## Privacy and Security

- Only synthetic student and teacher information should be used in development and demonstrations.
- Never commit passwords, API keys, database credentials, or other environment secrets.
- Use strong passwords for administrator accounts.
- Store student documents in private storage rather than publicly accessible directories.
- Generate unique filenames for uploaded documents.
- Validate uploaded file types and file sizes.
- Enforce authorization rules for student records and associated documents.
- Restrict audit log access to administrators.
- Restrict notification access and read-status updates to the owning user.
- Limit student document upload notifications to authorized administrator and staff accounts.
- Avoid storing sensitive information in audit log properties.
- Restrict application settings management to active administrators.
- Validate configuration values and escape configurable institution names in HTML.
- Keep application credentials outside database-backed settings.
- Preserve UTC timestamps and historical academic data when display preferences change.
- Reject overlapping academic-year and term periods and use transactional calendar writes with audit logging.
- Reject overlapping class and teacher lessons within an academic term, protect scheduled dependencies through participating write paths, and record timetable changes transactionally.
- Validate class timetable term filters against the class academic year while allowing teacher schedules to span academic years.
- Preserve the existing numeric academic-year fields when introducing new calendar records. Require registered years for newly created classes and year changes while allowing unchanged historical years during class edits.
- Allow only active administrators to create promotions, while allowing authorized staff to view progression history.
- Preserve attendance and examination results against the original enrollment after a promotion, and retain promotion-linked enrollment dates and statuses.
- Use transactions and consistent student-row locking for supported Active enrollment and promotion writes; note that an additional database-level constraint would be needed to enforce one Active enrollment independently of all application writers.
- Validate class, subject, and date filters for academic report downloads.
- Apply active-account and student archive authorization checks to PDF and XLSX exports.
- Keep academic report exports non-public and avoid caching student PDF responses.
- Record successful export metadata without copying private academic record contents into the audit log.
- Treat user-controlled spreadsheet values as text to prevent formula injection.
- Review authorization rules when adding new functionality.
- Test security-sensitive changes before merging them into `main`.

## Project Status

**Current status:** Academic timetable and scheduling management (locally verified on the feature branch), student management, student CSV import and export, student document management, teacher management, academic year and term management, academic class management and calendar integration, subject management, class-subject assignment, teacher-class-subject assignment, student enrollment management, student promotion and academic progression, attendance management, examination and grade management, academic reports **with PDF/XLSX downloads**, dashboard and statistics, audit logs and activity history, in-app notifications, authentication, role-based authorization, user management, account activation, user profile/account settings, advanced search and filtering, dashboard analytics, Notification Center enhancements, and application settings and configuration management have been implemented.

Academic years and terms can be created and updated by active administrators and viewed by active staff. Calendar date validation prevents overlapping years and terms, and the calendar integrates with Application Settings without replacing existing numeric year identifiers. Calendar writes and audit events use shared database transactions. The academic-class/calendar integration offers registered-year selection for new and changed class-year assignments while retaining unchanged historical values and showing registered calendar details on class profiles. See [Academic Calendar Documentation](docs/academic-calendar.md).

The application supports administrator-controlled teacher management, many-to-many relationships between academic classes and subjects, teacher assignments to class-subject combinations, weekly academic timetables with class/teacher overlap protection and read-only staff access, subject-based attendance recording, examination scheduling, student examination results, configurable grade calculation, a dashboard with academic and attendance summaries, and read-only academic reporting.

Student records can be imported and exported using CSV files. The import functionality validates individual records, identifies duplicates, and reports row-level errors while allowing valid records to be imported. The export functionality supports active student records and current search criteria, with protection against spreadsheet formula injection.

Student document management allows administrators and staff to upload, view, and download documents associated with student profiles according to their permissions. Supported formats include PDF, JPG, JPEG, and PNG, with a maximum upload size of 5 MB. Documents are stored using generated filenames in private storage. Document downloads enforce authentication and student ownership checks, while document deletion is restricted to administrators.

The timetable module provides term-specific weekly schedules for classes and teachers, with administrator-only scheduling changes and staff read access. It rejects conflicting class or teacher lessons, protects related records against incompatible edits, and audits committed timetable changes. Targeted independent-process MySQL checks have verified selected concurrency scenarios, without claiming exhaustive concurrency safety.

The dashboard displays summary cards, enrollment and attendance status totals, upcoming examinations, and recent student, enrollment, and attendance records. Archived student and teacher counts are visible to administrators only.

Academic reports include student enrollment, attendance, and examination history, as well as filtered attendance and examination summaries. Each report category supports PDF and Excel (XLSX) downloads. Attendance and examination exports support class, subject, and date filtering with readable labels and complete matching datasets. Administrators can view and export archived students' historical academic reports, while staff users are restricted to active student reports. Exports include authorization checks, audit events, and spreadsheet formula-injection protection.

Attendance sessions and examinations are linked to teaching assignments, while individual attendance records and examination results are linked to student enrollments. Promotions complete an original enrollment and create a separate Active destination enrollment, without moving historical attendance or examination results. The promotion history table connects source and destination enrollments and records the progression type, date, administrator, and related audit event. Historical academic records are protected through database relationships and deletion restrictions.

The audit logging system records important authentication, user management, student, teacher, enrollment, promotion, timetable, attendance, examination, academic class, academic year and term, CSV, document, and PDF/XLSX academic report export activities. Administrators can search and filter audit records and view individual activity details. Staff users cannot access the audit log interface.

Audit records are designed to exclude sensitive credentials and unnecessary personal information. Changes to student and teacher records are tracked through changed field names, while document audit entries avoid storing file paths, original filenames, descriptions, and file contents. The application provides a read-only audit log interface.

The in-app notification system informs other active users about student registration, enrollment creation, attendance session creation, examination scheduling, and student document uploads. Each user can view and mark their own notifications as read; document upload notifications are limited to administrators and staff.

Advanced search and filtering, dashboard analytics, and the enhanced Notification Center improve access to academic information and system activity. Notification filters, pagination, read actions, and accessible status indicators support day-to-day use.

The application settings module provides administrator-controlled institution information, academic defaults, and localization preferences. Registered academic calendar years appear as suggestions while the saved setting remains numeric. Settings updates are validated, cached, and audited. Configured branding is reflected in the authenticated navigation, a configured academic year is preselected in new class forms only when registered, and notification timestamps use the selected display preferences.

Automated testing and GitHub Actions support the development workflow. The test suite includes settings initialization, authorization, validation, caching, audit rollback, middleware configuration, localization, academic-report export security and completeness, student-promotion rules, historical-record preservation, and integration regression coverage. The most recent reported full local run on **November 28, 2026** passed **756 tests (2,742 assertions)** with a temporary 512 MB PHP CLI memory allowance; Laravel Pint passed **225 files**. Composer validation, platform checks, and the Vite production build passed. Separate local MySQL/InnoDB concurrency experiments exercised competing promotions as well as timetable lock contention, conflicting timetable creations, teacher deactivation against scheduling, and calendar renumbering against class creation; the observed outcomes preserved relevant records and audit counts. These results describe local verification of the Academic Timetable and Scheduling feature branch; independent browser smoke testing, full cross-process test automation, the final commit, and merge remain separate steps.

---

Built as a continuous learning and software development portfolio project.