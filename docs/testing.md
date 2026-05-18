# Testing Guide

## Running Tests

```bash
# All tests
php artisan test

# Specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Unit/Domains/Lead/LeadRepositoryTest.php

# With filter
php artisan test --filter=MetaPlatform
```

## Test Structure

```
tests/
├── Unit/
│   └── Domains/
│       ├── Agent/
│       │   ├── CommentReplyAgentTest.php    # Agent reply logic
│       │   └── LeadQualifierAgentTest.php    # Lead qualification
│       ├── Integration/
│       │   └── MetaPlatformTest.php          # Webhook payload parsing
│       └── Lead/
│           └── LeadRepositoryTest.php        # Lead CRUD + queries
├── Feature/
│   ├── Api/
│   │   ├── AgentApiTest.php                 # Agent health/chat endpoints
│   │   └── LeadApiTest.php                  # Lead CRUD API
│   └── WebhookTest.php                      # Webhook verification + handling
└── TestCase.php
```

## Factory Usage

```php
// Create with defaults
$lead = Lead::factory()->create();

// Custom states
$qualified = Lead::factory()->qualified()->create();
$fromComment = Lead::factory()->fromComment()->make();

// With relationships
$campaign = Campaign::factory()->has(Lead::factory()->count(5))->create();
```

## Writing Unit Tests

Mock external services:

```php
$this->router = Mockery::mock(SmartRouterInterface::class);
$this->router->shouldReceive('analyze')->once()->andReturn($analysis);

$agent = new CommentReplyAgent($this->router, ...);
$result = $agent->analyze('test message');
```

## Writing Feature Tests

Use `RefreshDatabase` for database-dependent tests:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_lead(): void
    {
        $response = $this->postJson('/api/leads', [...]);
        $response->assertStatus(201);
    }
}
```

## CI Pipeline

Tests run automatically on GitHub Actions (`.github/workflows/tests.yml`):

- PHP 8.4 + PostgreSQL + Redis
- Composer install, migration, test
- Node 22 + npm build
