# System Requirements
- PHP 8.1
- MySQL/MariaDB (used: )

# Setup Instructions
1. Copy .env.example and save as .env
2. Create new database and update DB_DATABASE in .env
3. Update DB_USERNAME and DB_PASSWORD in .env
4. Run php artisan migrate --seed
5. Run php artisan serve

# Development Notes
The default user of this project is: admin@admin.com with password 'password'.
