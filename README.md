# PHP/MySQL login page created from the PDFs

This small project combines the examples shown in the uploaded presentations:

- MySQL `users` table with `user_id`, `user_fullname`, `user_email`, and `user_passwordhash`.
- Registration form and registration handler.
- Login form and login handler using `password_verify()`.
- PHP session-based logged-in state.
- Logout page using `session_unset()` and `session_destroy()`.

## Files

- `schema.sql` — creates the `users` table.
- `config.php` — database connection and helper functions.
- `index.php` — landing page.
- `register.php` — registration page.
- `login.php` — login page.
- `dashboard.php` — protected logged-in page.
- `logout.php` — logout page.
- `style.css` — basic styling.

## Setup

1. Upload the files to a PHP-enabled server.
2. Import `schema.sql` in phpMyAdmin or run it in MySQL.
3. Edit `config.php` and set your real database credentials.
4. Open `index.php` in the browser.

## Notes

The lecture slides used `mysqli_*` and hard-coded example credentials. This version keeps the same idea but uses PDO prepared statements, does not include real credentials, and validates e-mail/password input.
