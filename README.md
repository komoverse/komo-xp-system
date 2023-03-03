# System Requirements
- PHP 8.1

# Setup Instructions
1. Copy .env.example and save as .env
2. Update DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env
3. Update XP_SECURITY_KEY in .env with 16-digit numbers, i.e: 1234567890123456
4. Run php artisan migrate
5. Run php artisan serve
