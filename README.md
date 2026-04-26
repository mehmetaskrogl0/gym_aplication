# FitBalance Web Starter Template

A clean and modern starter template for a Fitness and Nutrition Tracking application.

## Stack
- PHP 8+
- MySQL
- HTML5 + CSS3
- Bootstrap 5

## Project Structure
- `config.php` - PDO database configuration
- `index.php` - Main dashboard with Daily Snapshot
- `login.php` / `logout.php` - Basic session flow
- `edit_calorie_log.php` - Edit page for calorie logs
- `style.css` - Custom minimalist design
- `database.sql` - Initial database schema
- `includes/header.php` - Reusable navbar with profile dropdown
- `includes/sidebar.php` - Quick one-click action panel
- `includes/footer.php` - Reusable footer/scripts
- `includes/session.php` - Session, auth guard, CSRF helpers
- `includes/functions.php` - Flash messages and escaping helpers
- `actions/save_calorie_log.php` - Create calorie log
- `actions/update_calorie_log.php` - Update calorie log
- `actions/delete_calorie_log.php` - Delete calorie log
- `workout.php` - Workout module placeholder
- `steps.php` - Step counter placeholder

## Quick Start
1. Create MySQL database objects by running `database.sql`.
2. Update credentials in `config.php` or set env vars: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. Serve the folder with PHP:
   - `php -S localhost:8000`
4. Open `http://localhost:8000/login.php`.
