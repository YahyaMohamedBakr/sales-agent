# Development Guide

## Prerequisites

```bash
PHP 8.4+
PostgreSQL 16+ (with pgvector)
Redis 7+
Composer 2
Node.js 22+
```

## Setup

```bash
git clone <repo> sales_agent
cd sales_agent

cp .env.example .env
# Edit .env: set DB, Redis, Meta, OpenAI keys

composer install
npm install

php artisan key:generate
php artisan migrate
php artisan db:seed

npm run dev
php artisan serve
```

## Architecture

```
app/Domains/
├── Agent/          AI providers, SmartRouter, Orchestrator, Agents
├── Campaign/       Campaign CRUD + stats
├── Conversation/   Message logging
├── Integration/    Meta/WhatsApp/Email platforms
├── KnowledgeBase/  RAG document store
└── Lead/           Leads + EAV fields + scoring
```

## Adding an AI Provider

1. Create `app/Domains/Agent/AIProviders/YourProvider.php` extending `BaseProvider`
2. Add to `App\Domains\Agent\Enums\AIProvider` enum
3. Register in `App\Domains\Agent\Providers\AgentServiceProvider`
4. Add config in `config/agent.php`

## Adding an Agent

1. Create `app/Domains/Agent/Agents/YourAgent.php` implementing `AgentInterface`
2. Register in `App\Domains\Agent\Providers\AgentServiceProvider`
3. Add config in `config/agent.php`

## Queue Workers

```bash
# Start Horizon (recommended for production)
php artisan horizon

# Or individual workers
php artisan queue:work redis --queue=llm-calls --tries=3 --timeout=300
php artisan queue:work redis --queue=meta-webhooks --tries=3 --timeout=60
php artisan queue:work redis --queue=agent-actions --tries=1 --timeout=30
```

## Scheduling

The following commands are scheduled:

| Frequency | Command | Description |
|-----------|---------|-------------|
| Every 5 min | `agents:health-check` | Check AI provider health |

## Useful Commands

```bash
# Test LLM providers
php artisan agents:test

# Test Meta connection
php artisan agents:test-meta

# Watch queues (Horizon dashboard)
# Visit /horizon

# System monitoring
# Visit /pulse
```
