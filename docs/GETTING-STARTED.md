# Getting Started with CI-Inbox Development

**Welcome!** This guide gets you up and running in 30 minutes.

## Prerequisites

- **PHP** 8.1+ with extensions: pdo_mysql, imap, openssl, mbstring
- **MySQL** 8.0+ or MariaDB 10.6+
- **Composer** 2.5+
- **Web Server** Apache 2.4+ or nginx 1.18+ (or PHP built-in server for development)

### Quick Prerequisite Check

```bash
php -v               # Should show 8.1+
php -m | grep pdo    # Should show pdo_mysql
mysql --version      # Should show 8.0+
composer --version   # Should show 2.5+
```

## Quick Setup (5 minutes)

### 1. Clone & Install Dependencies

```bash
git clone https://github.com/hndrk-fegko/C-IMAP.git ci-inbox
cd ci-inbox
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ci_inbox
DB_USER=root
DB_PASSWORD=your_password

# Security
ENCRYPTION_KEY=your_32_char_hex_key  # Generate: php -r "echo bin2hex(random_bytes(32));"

# Optional: SMTP for email sending
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your_email
SMTP_PASSWORD=your_password
```

### 3. Initialize Database

```bash
# Create database (if not exists)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ci_inbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php database/migrate.php
```

### 4. Verify Installation

```bash
# Test environment loading
php tests/manual/test-env-loading.php

# Should output: ✅ Environment loaded successfully
```

### 5. Start Development Server

```bash
# Option A: PHP built-in server
php -S localhost:8080 -t src/public

# Option B: Use XAMPP/vHost pointing to src/public/
```

Access at: `http://localhost:8080`

## Project Structure (5 minutes)

```
ci-inbox/
├── src/                    # Source code
│   ├── core/              # Core infrastructure (Application, Container)
│   ├── modules/           # Reusable modules (logger, imap, smtp, etc.)
│   ├── app/               # Application layer
│   │   ├── Controllers/   # HTTP request handlers
│   │   ├── Services/      # Business logic
│   │   ├── Repositories/  # Data access
│   │   └── Models/        # Eloquent models
│   ├── config/            # Configuration files
│   ├── routes/            # api.php, web.php
│   ├── public/            # Web root (document root here!)
│   └── views/             # PHP templates
├── docs/                  # Documentation
│   └── dev/               # Developer documentation
├── tests/                 # Test suite
│   └── manual/            # Manual test scripts
├── database/              # Migrations
├── logs/                  # Log files (git-ignored)
└── data/                  # Runtime data (git-ignored)
```

## Key Documentation

| Document | Purpose |
|----------|---------|
| [vision.md](docs/dev/vision.md) | Project goals and use cases |
| [architecture.md](docs/dev/architecture.md) | System architecture details |
| [codebase.md](docs/dev/codebase.md) | Development environment setup |
| [roadmap.md](docs/dev/roadmap.md) | Feature timeline and milestones |
| [PROJECT-STATUS.md](docs/dev/PROJECT-STATUS.md) | Current project status |

## Your First Task (20 minutes)

### Task: Explore the API

1. **Start the server**
   ```bash
   php -S localhost:8080 -t src/public
   ```

2. **Test the health endpoint**
   ```bash
   curl http://localhost:8080/api/health
   # Expected: {"status":"ok",...}
   ```

3. **List threads**
   ```bash
   curl http://localhost:8080/api/threads
   # Expected: JSON array of threads
   ```

4. **Run a manual test**
   ```bash
   php tests/manual/thread-api-test.php
   # Expected: Multiple ✅ Success messages
   ```

## Essential Commands

```bash
# Database
php database/migrate.php          # Run migrations

# Testing
php tests/manual/<test>.php       # Run specific test
php tests/manual/webhook-test.php # Example test

# Logs
tail -f logs/app.log              # Watch application logs

# Server
php -S localhost:8080 -t src/public  # Development server
```

## Architecture Patterns

### Service Layer Pattern

All business logic goes in Services:

```php
class ThreadService {
    public function __construct(
        private ThreadRepositoryInterface $threadRepo,  // Interface!
        private LoggerInterface $logger
    ) {}
    
    public function getThread(int $id): ?Thread {
        $this->logger->info('Fetching thread', ['id' => $id]);
        return $this->threadRepo->find($id);
    }
}
```

### Key Rules

1. **Controllers are thin** - Only handle HTTP, delegate to Services
2. **Services use interfaces** - Never couple directly to implementations
3. **Log everything** - All important operations must be logged
4. **Layer abstraction** - Business logic never touches database directly

See `basics.txt` for complete guidelines.

## Common Issues

### Database Connection Failed
- Check `.env` database credentials
- Ensure MySQL service is running
- Verify database exists

### Class Not Found
- Run `composer install`
- Check namespace declarations
- Verify autoload configuration

### Permission Denied
- Ensure `logs/` is writable
- Check file permissions

## Next Steps

1. 📖 Read [architecture.md](docs/dev/architecture.md) for system overview
2. 🗺️ Check [roadmap.md](docs/dev/roadmap.md) for current priorities
3. 🧪 Explore tests in `tests/manual/` to understand features
4. 💡 Review `basics.txt` for coding guidelines

## Need Help?

- Check the `logs/app.log` for error details
- Review `docs/dev/` documentation
- Open an issue for persistent problems

---

**Happy coding!** 🚀
