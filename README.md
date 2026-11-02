# TEAMFLOW

A PHP/MySQL team task and project management system built with an MVC architecture.

## Requirements
- PHP 8.2+
- MySQL 8+ or MariaDB 10.6+
- Apache with `mod_rewrite` enabled (XAMPP is supported)
- PHP `pdo_mysql` and `fileinfo` extensions
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

## Collaboration
Project pages include a Kanban board with drag-and-drop and status-control alternatives, task comments, private attachments, and recent activity. Project members can view a monthly calendar of project and task deadlines and manage their in-app notifications. Calendar and notification previews are available as quick panels in the shared workspace header; the sidebar still opens the full calendar and notification pages. Attachments are limited to 10 MB and PDF, JPEG, PNG, GIF, WebP, plain text, or CSV; downloads require project access. Keep `storage/` outside the public web root in production and retain the upload access-control file when deploying.

## Analytics and reporting
Open **Reports** from the workspace sidebar to review progress for accessible projects, task completion and overdue totals, and active team members' assigned-task performance. Administrators see all projects; other users' reports are limited to projects they belong to. Use **Export project report · CSV** to download the project-level summary.

## Security and production deployment
Copy `.env.example` to `.env`, set a unique database password, and keep `.env` outside the web root and out of source control. Set `APP_DEBUG=false` in production and configure `APP_URL` for the public HTTPS address. Use HTTPS, a least-privilege MySQL account, current supported PHP/MySQL releases, and Apache with `mod_rewrite`; point the document root at `public/`. If TLS terminates at a reverse proxy, ensure PHP receives the HTTPS state so session cookies are marked secure. Keep `storage/` private and writable only by the application account. Configure PHP upload limits to accommodate the 10 MB attachment limit (for example, `upload_max_filesize=10M` and `post_max_size=12M`). The application sends restrictive browser security headers, uses strict session cookie settings, regenerates session IDs after sign-in, validates CSRF tokens, and enforces project access on private attachment downloads.

### Backup and restore
Back up both the MySQL database and `storage/uploads/` to encrypted, access-controlled storage, and regularly test restoring them to an isolated environment. For XAMPP, run the following from a protected backup directory; `--password` prompts for the database account password rather than putting it in the command:

```bat
"C:\xampp\mysql\bin\mysqldump.exe" --single-transaction --routines --triggers --host=127.0.0.1 --user=teamflow_backup --password teamflow > teamflow-backup.sql
```

Restore only after verifying the target database and backup:

```bat
"C:\xampp\mysql\bin\mysql.exe" --host=127.0.0.1 --user=teamflow_restore --password teamflow < teamflow-backup.sql
```

`database/schema.sql` initializes the database and drops existing tables; use it only for a fresh installation. Do not import it over a production database. Apply reviewed migrations for future schema changes and rehearse restores before a release.

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
