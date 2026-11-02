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
4. Copy `.env.example` to `.env` and update the database credentials.
5. Visit `http://localhost/TEAMFLOW/public/`.

If your folder is named differently, update `APP_URL` in `.env` and the `RewriteBase` in `public/.htaccess`.

## Initial administrator
Create the first administrator using the one-time setup script:
`http://localhost/TEAMFLOW/public/setup-admin`

The setup route is intentionally available only while there are no users. It creates an administrator account and becomes unavailable once an account exists.

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
