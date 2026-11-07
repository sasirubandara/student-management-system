# Student Management System

[![Laravel CI](https://github.com/sasirubandara/student-management-system/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/sasirubandara/student-management-system/actions/workflows/ci.yml)

A web-based Student Management System built with PHP and Laravel. The application provides student record management, authentication, role-based authorization, and administrator-controlled user account management.

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
- Restore archived student records.
- Track student enrollment dates and statuses.

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
| View user directory | Yes | No |
| Create staff accounts | Yes | No |
| Manage user roles | Yes | No |
| Activate/deactivate accounts | Yes | No |

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.3+ | Backend programming language |
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

- PHP 8.3 or later
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

Copy the example environment file:

```bash
cp .env.example .env
```

For Windows PowerShell, you can also use:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure the database

The example environment is configured to use SQLite by default.

Create the SQLite database file:

```bash
touch database/database.sqlite
```

On Windows PowerShell:

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

- Class management
- Subject management
- Teacher management
- Student enrollment
- Attendance management
- Examination and grade management

### Advanced Features

- Dashboard and statistics
- Reports
- CSV import and export
- Audit logs
- Notifications
- Advanced search and filtering

### Further Quality Improvements

- Additional automated test coverage
- Application security improvements
- Database backup and recovery
- Deployment preparation
- Performance optimization

## Project Structure

```text
student-management-system/
├── app/
│   ├── Console/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   └── Policies/
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

- Only synthetic student information should be used in development and demonstrations.
- Never commit passwords, API keys, database credentials, or other environment secrets.
- Use strong passwords for administrator accounts.
- Review authorization rules when adding new functionality.
- Test security-sensitive changes before merging them into `main`.

## Project Status

**Current status:** Core student management, authentication, role-based authorization, user management, and account activation have been implemented.

Development continues incrementally, with automated testing and GitHub Actions supporting the development workflow.

---

Built as a continuous learning and software development portfolio project.