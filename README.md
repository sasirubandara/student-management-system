# Student Management System

[![Laravel CI](https://github.com/sasirubandara/student-management-system/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/sasirubandara/student-management-system/actions/workflows/ci.yml)

A web-based Student Management System built with PHP and Laravel. The application provides student and teacher management, academic class and subject management, class-subject assignments, teacher-class-subject assignments, student enrollment management, subject-based attendance management, examination and grade management, authentication, role-based authorization, and administrator-controlled user account management.

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

### Student Enrollment Management

- Connect students with academic classes through enrollment records.
- View enrollment listings and individual enrollment details.
- Search and paginate enrollment records.
- Create new student enrollments.
- Update enrollment dates, statuses, and administrative notes.
- Support three enrollment statuses: **Active**, **Completed**, and **Withdrawn**.
- Prevent duplicate enrollment records for the same student and academic class.
- Reject enrollment creation when the selected student or academic class is inactive or archived.
- Preserve enrollment records as historical records rather than permanently deleting them.

**Enrollment history and duplicate prevention**

Each student and academic class combination can have only one enrollment record. A unique database constraint prevents another record from being created for the same combination, even after an enrollment has been marked as Completed or Withdrawn. Existing enrollment records can instead be updated.

Enrollment records do not use soft deletes. Database relationships protect enrollment records against accidental deletion of related student or academic class records.

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

### Academic Class Management

- Create and manage academic classes.
- Store class names, codes, grade levels, and academic years.
- View individual academic class details.
- Search classes by name and class code.
- Paginate academic class records.
- Update academic class information.
- Archive academic classes using soft deletes.

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
| Create teaching assignments | Yes | No |
| Update teaching assignments | Yes | No |
| Remove teaching assignments | Yes | No |
| View attendance sessions | Yes | Yes |
| Create attendance sessions | Yes | Yes |
| View student attendance records | Yes | Yes |
| Record and update student attendance | Yes | Yes |
| Remove empty attendance sessions | Yes | No |
| Remove individual attendance records | Yes | No |
| View examinations | Yes | Yes |
| Create examinations | Yes | Yes |
| Update examinations | Yes | Yes |
| Remove examinations | Yes | No |
| View examination results | Yes | Yes |
| Record and update examination results | Yes | Yes |
| Remove individual examination results | Yes | No |
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

Run the dashboard and statistics feature tests:

```bash
php artisan test tests/Feature/DashboardTest.php
```

Run the grade calculator unit tests:

```bash
php artisan test tests/Unit/GradeCalculatorTest.php
```

Run tests matching a particular name:

```bash
php artisan test --filter=AuthenticationTest
```

The automated test environment is configured to use an in-memory SQLite database through `phpunit.xml`. This keeps test records separate from the development database.

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
git pull origin main
git switch -c feature/your-feature-name
```

## Project Roadmap

The following features are potential future development milestones.

### Academic Management

Potential future academic functionality can be added as the application develops.

### Advanced Features

- Reports.
- CSV import and export.
- Audit logs.
- Notifications.
- Advanced search and filtering.

### Further Quality Improvements

- Additional automated test coverage.
- Application security improvements.
- Database backup and recovery.
- Deployment preparation.
- Performance optimization.

## Project Structure

```text
student-management-system/
├── app/
│   ├── Console/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   ├── Policies/
│   └── Services/
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
├── routes/
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
- Review authorization rules when adding new functionality.
- Test security-sensitive changes before merging them into `main`.

## Project Status

**Current status:** Student management, teacher management, academic class management, subject management, class-subject assignment, teacher-class-subject assignment, student enrollment management, attendance management, examination and grade management, dashboard and statistics, authentication, role-based authorization, user management, and account activation have been implemented.

The application supports administrator-controlled teacher management, many-to-many relationships between academic classes and subjects, teacher assignments to class-subject combinations, subject-based attendance recording, examination scheduling, student examination results, configurable grade calculation, and a dashboard with academic and attendance summaries.

The dashboard displays summary cards, enrollment and attendance status totals, upcoming examinations, and recent student, enrollment, and attendance records. Archived student and teacher counts are visible to administrators only.

Attendance sessions and examinations are linked to teaching assignments, while individual attendance records and examination results are linked to student enrollments. Historical academic records are preserved through database relationships and deletion restrictions.

Automated testing and GitHub Actions support the development workflow.

---

Built as a continuous learning and software development portfolio project.