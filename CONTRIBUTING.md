# Contributing to CI-Inbox

**Thank you for contributing!** 🎉

CI-Inbox is a collaborative IMAP inbox management system for small teams. We welcome contributions that help improve the project.

## Getting Started

1. **Read the documentation**: Start with [docs/dev/codebase.md](docs/dev/codebase.md) for setup
2. **Understand the architecture**: Review [docs/dev/architecture.md](docs/dev/architecture.md)
3. **Check current priorities**: See [docs/dev/roadmap.md](docs/dev/roadmap.md)

## Development Workflow

### Setting Up Your Environment

1. **Clone the repository**
   ```bash
   git clone <repository-url> ci-inbox
   cd ci-inbox
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. **Set up database**
   ```bash
   php database/migrate.php
   ```

5. **Verify installation**
   ```bash
   php tests/manual/test-env-loading.php
   ```

### Making Changes

1. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Implement your changes**
   - Follow PSR-12 code style
   - Use strict types (`declare(strict_types=1)`) in all PHP files
   - Add logging for important operations
   - Use layer abstraction (Service → Repository pattern)

3. **Test your changes**
   ```bash
   php tests/manual/<relevant-test>.php
   ```

4. **Commit with clear messages**
   ```bash
   git commit -m "feat(scope): Add feature description"
   ```

5. **Create a Pull Request**
   - Reference any related issues
   - Describe what your PR does
   - Include testing steps

## Coding Standards

### Code Style

- **PSR-12** for PHP code style
- **Strict types** (`declare(strict_types=1)`) in all PHP files
- **Type hints** for all parameters and return types
- **Property promotion** (PHP 8.1+) for constructor injection

### Architecture Guidelines

Follow the principles from `basics.txt`:

1. **Layer Abstraction**
   ```php
   // ✅ CORRECT: Service depends on interface
   class ThreadService {
       public function __construct(
           private ThreadRepositoryInterface $threadRepo,
           private LoggerInterface $logger
       ) {}
   }
   
   // ❌ WRONG: Direct database access
   class ThreadService {
       public function process() {
           $pdo->query("SELECT * FROM threads");
       }
   }
   ```

2. **Logging** - Always log important operations
   ```php
   $this->logger->info('Processing email', [
       'message_id' => $email->messageId,
       'subject' => $email->subject
   ]);
   ```

3. **Thin Controllers** - Business logic goes in Services
   ```php
   // Controller only handles HTTP
   $app->get('/api/threads/{id}', function ($request, $response, $args) use ($container) {
       $service = $container->get(ThreadService::class);
       $thread = $service->getThread((int)$args['id']);
       return jsonResponse($response, ['success' => true, 'data' => $thread]);
   });
   ```

### Documentation

- Update relevant documentation when making changes
- Add PHPDoc blocks for classes and public methods
- Include usage examples for new features

## Testing

### Running Existing Tests

```bash
# Individual tests
php tests/manual/webhook-test.php
php tests/manual/thread-api-test.php

# Check logs after tests
tail -f logs/app.log
```

### Writing New Tests

Follow the pattern in `tests/manual/README.md`:

```php
<?php
declare(strict_types=1);

/**
 * Feature Manual Test
 * 
 * Description of what this tests.
 * Usage: php tests/manual/your-test.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// ... test implementation
```

## Pull Request Checklist

Before submitting your PR:

- [ ] Code follows PSR-12 style
- [ ] All files have `declare(strict_types=1)`
- [ ] Logging added for data-modifying operations
- [ ] Layer abstraction followed (no direct DB in controllers)
- [ ] Tests pass
- [ ] Documentation updated
- [ ] Commit messages are clear
- [ ] No merge conflicts

## Code Review Process

1. All PRs require at least one review
2. Address review feedback promptly
3. Keep PRs focused and reasonably sized
4. Complex changes should include architecture notes

## Questions?

- Check the documentation in `docs/dev/`
- Review existing code for patterns
- Open an issue for discussion

---

**Thank you for helping improve CI-Inbox!** 🙏
