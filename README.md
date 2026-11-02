# TEAMFLOW

A PHP/MySQL team task and project management system built with an MVC architecture.

## Requirements
- PHP 8.2+
- MySQL 8+ or MariaDB 10.6+
- Apache with `mod_rewrite` enabled (XAMPP is supported)
- Composer (optional for this starter; the application currently uses native PHP)

## Local setup (XAMPP)
1. Extract `TEAMFLOW` into `C:\xampp\htdocs\`.
2. Start Apache and MySQL from XAMPP.
3. Open phpMyAdmin and import `database/schema.sql`.
4. Configure the database credentials in `.env` (create the file if it does not exist).
5. Visit `http://localhost/TEAMFLOW/public/`.

If your folder is named differently, update `APP_URL` in `.env` and the `RewriteBase` in `public/.htaccess`.

## Initial administrator
Create the first administrator using the one-time setup script:
`http://localhost/TEAMFLOW/public/setup-admin`

The setup route is intentionally available only while there are no users. It creates an administrator account and becomes unavailable once an account exists.

## User and team management
Sign in as an administrator and open **People** in the workspace sidebar (or `/users`) to create accounts and manage names, email addresses, roles, account status, and password resets. Open **Roles & permissions** (or `/roles`) to create roles and assign permissions; this editor requires an active Administrator with the `roles.manage` permission, and the built-in Administrator role is protected from edits. User management is guarded by `users.manage`. New users receive an administrator-provided initial password of at least 12 characters; share it securely. The last active administrator cannot be suspended, archived, or demoted. Inactive accounts lose protected access immediately.

## Project and task management
Open **Projects** from the workspace sidebar to create and view projects. Project owners and assigned project managers can update project details and membership, create and assign tasks, add subtasks, set priorities and dates, and manage checklists. Members can update the status and checklist of tasks assigned to them. Project access is limited to project members and administrators; project and task operations also enforce role permissions. The dashboard shows live counts for active projects and assigned, completed, and overdue tasks, along with the current development phase.

## Architecture
- `app/Controllers` — HTTP request handling
- `app/Models` — database access
- `app/Services` — business logic
- `app/Middleware` — authentication and authorization
- `app/Views` — templates
- `config` — application and database configuration
- `database` — schema and future migrations/seeders
- `public` — only public web files; configure this as the web root in production
- `routes` — route definitions
- `storage` — logs and private uploads

## Important deployment note
For production, point the web server document root to the `public/` directory. Keep `.env`, `app/`, `config/`, `database/`, and `storage/` outside the publicly accessible directory.
