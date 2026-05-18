# Agent System

## Architecture

```
Inbound Message (comment, messenger, webhook)
       │
       ▼
┌──────────────────┐
│   Orchestrator   │  ← decides which agent handles this
│   (Chain of Resp)│
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│  FilterAgent     │  ← spam? irrelevant language?
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│  AnalyzeAgent    │  ← intent, sentiment, entities
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│  RouteAgent      │  ← CommentReply / LeadQualifier / Support
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│  ActionAgent     │  ← reply, DM, qualify, escalate
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│  LogAgent        │  ← audit trail
└──────────────────┘
```

## Available Agents

| Agent | Type | Purpose |
|---|---|---|
| `CommentReplyAgent` | Auto | Replies to Facebook/Instagram comments |
| `LeadQualifierAgent` | Auto | Engages in Messenger to qualify leads |
| `SupportAgent` | Auto | After-sales support |
| `Orchestrator` | Manager | Routes messages to the right agent |

## Adding a New Agent

### 1. Create the Agent class

```php
<?php

namespace App\Domains\Agent\Agents;

use App\Domains\Agent\Contracts\AgentInterface;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;

class MyNewAgent implements AgentInterface
{
    private ModelConfig $modelConfig;

    public function __construct(
        private SmartRouterInterface $router,
    ) {
        $this->modelConfig = new ModelConfig(
            provider: AIProvider::OpenAI,
            model: 'gpt-4o-mini',
        );
    }

    public function handle(string $message, array $context = []): string
    {
        $response = $this->router->chat(
            messages: [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $message],
            ],
            preferred: $this->modelConfig->provider,
        );

        return $response->content;
    }

    public function shouldHandle(string $message, array $context = []): bool
    {
        // Return true if this agent should handle this message
        return true;
    }

    public function analyze(string $message): AnalysisResult
    {
        return $this->router->analyze(
            text: $message,
            preferred: $this->modelConfig->provider,
        );
    }

    public function name(): string
    {
        return 'my_new_agent';
    }

    public function setModelConfig(ModelConfig $config): void
    {
        $this->modelConfig = $config;
    }

    public function getModelConfig(): ModelConfig
    {
        return $this->modelConfig;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a helpful sales assistant. Your job is to...
PROMPT;
    }
}
```

### 2. Register in Orchestrator

```php
// In AgentServiceProvider or Orchestrator
$orchestrator->registerAgent(new MyNewAgent($router));
```

### 3. Add config (optional)

```php
// config/agent.php
'agents' => [
    'my_new_agent' => [
        'enabled' => true,
        'provider' => 'smart',
    ],
],
```

## Agent System Prompt Guidelines

Each agent has a system prompt that defines its behavior:

- **Personality**: Friendly, professional, helpful
- **Language**: Arabic & English support
- **Goal**: Qualify leads & gather information
- **Rules**:
  - Never ask for passwords or sensitive data
  - Always be transparent about being an AI
  - If customer is frustrated, offer human handoff
  - Collect info step by step (don't ask everything at once)
