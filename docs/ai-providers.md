# AI Providers & Smart Router

## Supported LLM Providers

| Provider | Tier | API Key Required | Free Tier | Models |
|---|---|---|---|---|
| **OpenAI** | Premium | ✅ | ❌ | GPT-4o, GPT-4o-mini, o1, o3-mini |
| **Anthropic** | Premium | ✅ | ❌ | Claude 3.5 Sonnet, Claude 3 Haiku |
| **Google Gemini** | Premium | ✅ | ✅ (rate limited) | Gemini 1.5 Pro, 1.5 Flash, 2.0 Flash |
| **Groq** | Free | ✅ | ✅ (fast!) | Llama 3.1 70B, 8B, Mixtral 8x7B |
| **Ollama** | Local | ❌ | ✅ (100% free) | Llama 3, Qwen 2.5, DeepSeek, many more |
| **OpenRouter** | Aggregator | ✅ | ✅ (free models) | 200+ models from all providers |
| **Mistral** | Premium | ✅ | ❌ | Mistral Large, Small, Codestral |
| **DeepSeek** | Standard | ✅ | ✅ | DeepSeek V2, Coder |
| **Together AI** | Standard | ✅ | ❌ | Llama 3, DeepSeek, Qwen |
| **Cohere** | Premium | ✅ | ❌ | Command R+, Command R |
| **OpenCode Zen** | **Free** | ❌ (optional) | **✅ مجاني** | **Big Pickle (200K ctx)**, DeepSeek V4 Flash, Nemotron 3 |

## How It Works

```
User sends message
       │
       ▼
┌──────────────┐
│  SmartRouter │  ← decides which provider to use
│              │
│  Strategy:   │
│  • smart     │  → tries Premium → Standard → Free → Local
│  • priority  │  → in registration order
│  • cost      │  → Local → Free → Standard → Premium
│  • perf      │  → fastest latency first
└──────┬───────┘
       │
       ▼
┌──────────────┐     ┌──────────────┐
│  OpenAI      │     │  Groq        │
│  (GPT-4o)    │────▶│  (Llama 3)   │  ← fallback if rate limited
└──────────────┘     └──────────────┘
                            │
                            ▼
                     ┌──────────────┐
                     │  Ollama      │
                     │  (local)     │  ← last resort (always available)
                     └──────────────┘
```

## Smart Router Strategy

### Smart Mode (recommended)
```
1. Try Premium providers (GPT-4o, Claude 3.5 Sonnet)
2. If ALL premium are rate-limited → Standard (GPT-4o-mini, Gemini Flash)
3. If Standard also fails → Free (Groq Llama, Gemini Free)
4. Last resort → Local (Ollama)
```

### Cost-Optimized Mode
```
1. Local Ollama (free)
2. Free tier (Groq, Google free)
3. Standard (GPT-4o-mini, DeepSeek)
4. Premium (GPT-4o, Claude)
```

## Configuration

### .env variables

```bash
# Default provider (openai, anthropic, google, groq, ollama, openrouter, smart)
AGENT_PROVIDER=smart

# Router strategy (smart, priority, cost_optimized, performance)
AGENT_ROUTER_STRATEGY=smart

# Temperature & tokens
AGENT_TEMPERATURE=0.7
AGENT_MAX_TOKENS=2048

# ---- Provider API Keys ----
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GOOGLE_API_KEY=AIza...
GROQ_API_KEY=gsk_...
OPENROUTER_API_KEY=sk-...
MISTRAL_API_KEY=...
DEEPSEEK_API_KEY=...
TOGETHER_API_KEY=...

# Override specific model per provider
OPENAI_MODEL=gpt-4o-mini      # cheaper default

# Ollama (local)
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=llama3
```

### Config file: `config/agent.php`

```php
'default' => 'smart',              // default provider
'router_strategy' => 'smart',      // routing strategy
'temperature' => 0.7,
'max_tokens' => 2048,

// Per-agent provider override
'agents' => [
    'comment_reply' => [
        'provider' => 'smart',      // smart routing for comments
    ],
    'lead_qualifier' => [
        'provider' => 'gpt-4o',     // always use GPT-4o for qualification
    ],
],
```

## Adding a New Provider

1. Create a new class in `app/Domains/Agent/AIProviders/` extending `BaseProvider`
2. Implement all required methods
3. Add the provider to `AIProvider` enum in `Enums/AIProvider.php`
4. Add credentials to `config/agent.php`
5. Register in `AgentServiceProvider::registerProviders()`

```php
// Example: MyCustomProvider.php
class MyCustomProvider extends BaseProvider
{
    public function provider(): AIProvider
    {
        return AIProvider::MyCustom;  // add to AIProvider enum first
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        // Your API call here
    }

    public function analyze(string $text, ModelConfig $config): AnalysisResult
    {
        // Use chat internally
    }

    public function embed(string $text): array
    {
        // Your embedding implementation
    }

    protected function checkHealth(): ProviderStatus
    {
        // Check if your API is reachable
    }

    protected function getRateLimitPerMinute(): ?int
    {
        return 100;
    }

    protected function getRateLimitPerHour(): ?int
    {
        return 5000;
    }
}
```

## Usage in Code

```php
// Via dependency injection
class CommentController
{
    public function __construct(
        private SmartRouterInterface $router,
    ) {}

    public function handleComment(Request $request)
    {
        // Use specific provider
        $response = $this->router->chat(
            messages: [['role' => 'user', 'content' => $text]],
            preferred: AIProvider::OpenAI,
        );

        // Use smart routing
        $response = $this->router->chat(
            messages: [['role' => 'user', 'content' => $text]],
            strategy: RouterStrategy::Smart,
        );

        // Analyze text (auto-routed)
        $analysis = $this->router->analyze($comment->text);
    }
}

// Via facade or helper
$router = app(SmartRouterInterface::class);
$health = $router->healthReport();
$available = $router->availableProviders();
```
