# Continuation Guide — المراحل الجاية

هذا الملف يشرح كل الخطوات المطلوبة لإكمال المشروع بعد Phase 0 + Phase 1.
كل Phase مكتوبة كاملة بالملفات المطلوبة، الكود، والتوثيق.

---

## Phase 2: WhatsApp + Email Channels

### 2.1 WhatsApp Integration

#### الملفات المطلوب إنشاؤها:

**1. WhatsApp Platform Implementation**
`app/Domains/Integration/Platforms/WhatsAppPlatform.php`

```php
<?php

namespace App\Domains\Integration\Platforms;

use App\Domains\Integration\Contracts\SocialPlatformInterface;
use Illuminate\Support\Facades\Http;

class WhatsAppPlatform implements SocialPlatformInterface
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $baseUrl = 'https://graph.facebook.com/v21.0';

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.access_token');
    }

    public function platformName(): string { return 'whatsapp'; }
    public function setAccessToken(string $token): void { $this->accessToken = $token; }
    public function setPageId(string $pageId): void {}

    public function sendMessage(string $recipientId, string $message, array $options = []): array
    {
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $recipientId,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        return $response->json();
    }

    public function getPageInfo(): array { return []; }
    public function getComments(string $postId, array $params = []): \Illuminate\Support\Collection { return collect(); }
    public function replyToComment(string $commentId, string $message): array { return []; }
    public function getConversation(string $conversationId): array { return []; }
    public function markAsRead(string $recipientId): bool { return true; }
}
```

**2. WhatsApp Webhook Handler** — `app/Http/Controllers/WebhookController.php` — أضف method:

```php
protected function handleWhatsAppEvent(array $payload): void
{
    foreach ($payload['entry'] ?? [] as $entry) {
        foreach ($entry['changes'] ?? [] as $change) {
            if (($change['field'] ?? '') === 'messages') {
                $value = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];

                foreach ($messages as $msg) {
                    $from = $msg['from'] ?? '';
                    $text = $msg['text']['body'] ?? '';

                    $this->orchestrator->handleMessage([
                        'type' => 'message',
                        'sender_id' => "wa:{$from}",
                        'message' => $text,
                        'channel' => 'whatsapp',
                    ]);
                }
            }
        }
    }
}
```

**3. أضف Webhook Route** — `routes/web.php`:
```php
Route::post('/webhook/whatsapp', [WebhookController::class, 'handleWhatsApp'])
    ->withoutMiddleware(VerifyCsrfToken::class);
```

**4. سجل الـ Provider** — `bootstrap/providers.php` — أضف:
```php
$this->app->bind(SocialPlatformInterface::class . ':whatsapp', WhatsAppPlatform::class);
```

**.env مفاتيح:**
```bash
WHATSAPP_PHONE_NUMBER_ID=123456
WHATSAPP_ACCESS_TOKEN=EAAT...
```

---

### 2.2 Email Integration

#### الملفات المطلوب إنشاؤها:

**1. Email Service** — `app/Domains/Integration/Services/EmailService.php`

```php
<?php

namespace App\Domains\Integration\Services;

use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function send(string $to, string $subject, string $body): void
    {
        Mail::html($body, function ($message) use ($to, $subject) {
            $message->to($to)
                ->subject($subject)
                ->from(config('mail.from.address'), config('mail.from.name'));
        });
    }

    public function sendTemplate(string $to, string $template, array $data): void
    {
        Mail::send("emails.{$template}", $data, function ($message) use ($to) {
            $message->to($to)
                ->subject($data['subject'] ?? 'رسالة من AI Sales Agent');
        });
    }
}
```

**2. Email Templates** — `resources/views/emails/`:
- `welcome.blade.php` — ترحيب بالعميل الجديد
- `qualification.blade.php` — تأكيد استلام البيانات
- `offer.blade.php` — إرسال عرض سعر

**3. Email Channel Agent** — `app/Domains/Agent/Agents/EmailAgent.php`
- يرسل رسائل متابعة للـ leads اللي شاركوا إيميل
- يدعم حملات البريد المجدولة (scheduled campaigns)

**.env مفاتيح:**
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG....
```

---

## Phase 3: Dashboard & Analytics

### 3.1 Inertia + React Setup

```bash
composer require laravel/jetstream
php artisan jetstream:install inertia
npm install
npm run build
```

أو يدوياً:
```bash
composer require inertiajs/inertia-laravel
npm install @inertiajs/react react react-dom tailwindcss
```

### 3.2 Dashboard Files

#### الصفحات المطلوبة:

**1. Leads Dashboard** — `resources/js/Pages/Leads/Index.tsx`
- جدول بالـ Leads (صفحة, اسم, تليفون, سكور, حالة, كامبين)
- فلاتر: status, source, campaign_id, score range
- بحث بالاسم/التليفون/الإيميل
- Pagination

**2. Lead Detail** — `resources/js/Pages/Leads/Show.tsx`
- معلومات الـ Lead
- تاريخ المحادثات (Conversation Timeline)
- Agent Actions Log (model, tokens, time, response)
- Lead Field Values (EAV fields)
- Manual score adjustment

**3. Conversations Timeline** — `resources/js/Components/ConversationTimeline.tsx`
- Bubble Chat UI
- Inbound (يمين) vs Outbound (يسار)
- Agent action details لكل رد
- Channel icon (Messenger, WhatsApp, Comment)

**4. Campaigns Dashboard** — `resources/js/Pages/Campaigns/Index.tsx`
- قائمة الحملات الإعلانية
- Stats لكل حملة (total leads, qualified, converted, rate)

**5. Agent Monitoring** — `resources/js/Pages/Agent/Monitoring.tsx`
- LLM Provider Health (online/offline, rate limit, latency)
- Real-time agent actions log
- Token usage chart

**6. Analytics** — `resources/js/Pages/Analytics/Index.tsx`
- Lead source pie chart
- Score distribution histogram
- Conversion funnel
- Daily new leads chart

**7. Knowledge Base Manager** — `resources/js/Pages/KnowledgeBase/Index.tsx`
- CRUD للمستندات
- تصنيف حسب category
- بحث

### 3.3 Controllers (باقي)

| Controller | Routes |
|---|---|
| `Api/CampaignController.php` | CRUD campaigns + stats |
| `Api/ConversationController.php` | عرض محادثات Lead |
| `Api/KnowledgeBaseController.php` | CRUD knowledge base |
| `Api/AgentController.php` | health, chat, analyze endpoints |
| `Api/AnalyticsController.php` | stats, charts data |

**مثال Agent Controller:**
```php
// app/Http/Controllers/Api/AgentController.php
class AgentController extends Controller
{
    public function __construct(
        private SmartRouterInterface $router,
        private Orchestrator $orchestrator,
    ) {}

    public function health(): JsonResponse
    {
        return response()->json($this->router->availableProviders());
    }

    public function chat(Request $request): JsonResponse
    {
        $msg = $request->input('message');
        $provider = $request->input('provider', 'smart');

        $response = $this->router->chat(
            messages: [['role' => 'user', 'content' => $msg]],
            preferred: AIProvider::tryFrom($provider),
        );

        return response()->json($response);
    }

    public function analyze(Request $request): JsonResponse
    {
        $analysis = $this->router->analyze($request->input('text'));
        return response()->json($analysis);
    }
}
```

### 3.4 API Routes (إضافة لـ routes/api.php)

```php
Route::apiResource('campaigns', CampaignController::class);
Route::apiResource('knowledge-base', KnowledgeBaseController::class);

Route::get('leads/{lead}/conversations', [ConversationController::class, 'index']);

Route::get('agent/health', [AgentController::class, 'health']);
Route::post('agent/chat', [AgentController::class, 'chat']);
Route::post('agent/analyze', [AgentController::class, 'analyze']);

Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
Route::get('analytics/leads-by-source', [AnalyticsController::class, 'leadsBySource']);
Route::get('analytics/leads-by-day', [AnalyticsController::class, 'leadsByDay']);
```

---

## Phase 4: Testing & Deployment

### 4.1 Unit Tests

#### Lead Domain Tests
`tests/Unit/Domains/Lead/LeadRepositoryTest.php`
```php
test('creates lead via repository', function () {
    $repo = app(LeadRepositoryInterface::class);
    $lead = $repo->create(['name' => 'أحمد', 'source' => 'comment']);
    expect($lead->name)->toBe('أحمد');
});

test('finds lead by psid', function () {
    Lead::factory()->create(['psid' => 'test_psid']);
    $repo = app(LeadRepositoryInterface::class);
    expect($repo->findByPsid('test_psid'))->not->toBeNull();
});
```

#### Agent Tests
`tests/Unit/Domains/Agent/CommentReplyAgentTest.php`
```php
test('analyzes comment intent', function () {
    $agent = app(CommentReplyAgent::class);
    $analysis = $agent->analyze('كم سعر المنتج؟');
    expect($analysis->intent)->toBe('pricing_inquiry');
});

test('skips irrelevant comments', function () {
    $agent = app(CommentReplyAgent::class);
    expect($agent->shouldHandle('spam link here'))->toBeFalse();
});
```

#### Meta Platform Tests
`tests/Unit/Domains/Integration/MetaPlatformTest.php`
```php
test('parses webhook comment payload', function () {
    $payload = [
        'entry' => [[
            'id' => '123',
            'changes' => [[
                'field' => 'feed',
                'value' => [
                    'comment_id' => '456',
                    'message' => 'مرحبا',
                    'from' => ['id' => '789', 'name' => 'أحمد'],
                ],
            ]],
        ]],
    ];

    $events = MetaPlatform::parseWebhookPayload($payload);
    expect($events)->toHaveCount(1);
    expect($events[0]['type'])->toBe('comment');
});
```

### 4.2 Feature Tests

`tests/Feature/WebhookTest.php`
```php
test('verifies webhook', function () {
    config()->set('services.meta.webhook_verify_token', 'test123');
    $response = $this->get('/webhook/meta?hub_mode=subscribe&hub_verify_token=test123&hub_challenge=challenge123');
    $response->assertSee('challenge123');
    $response->assertStatus(200);
});

test('rejects wrong verify token', function () {
    $response = $this->get('/webhook/meta?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=test');
    $response->assertStatus(403);
});
```

### 4.3 Factories

`database/factories/LeadFactory.php`
```php
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'psid' => fake()->uuid(),
            'name' => fake()->name(),
            'phone' => '05' . fake()->numerify('########'),
            'email' => fake()->email(),
            'source' => fake()->randomElement(['comment', 'messenger', 'whatsapp']),
            'score' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(['new', 'contacted', 'qualifying', 'qualified']),
        ];
    }

    public function qualified(): static
    {
        return $this->state(fn () => ['score' => 70, 'status' => 'qualified']);
    }
}
```

إضافة factories تانية:
- `CampaignFactory`
- `ConversationFactory`
- `KnowledgeBaseFactory`

### 4.4 Seeders

`database/seeders/DatabaseSeeder.php`
```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Campaign::factory(3)->create();
        Lead::factory(50)->create();
        KnowledgeBase::factory(10)->create();
        AgentConfig::factory(3)->create();
    }
}
```

### 4.5 GitHub Actions / CI

`.github/workflows/tests.yml`
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pgsql, redis
      - run: composer install
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: php artisan migrate --force
      - run: php artisan test
```

---

## Phase 5: Production Readiness

### 5.1 Horizon (Queue Monitoring)
```bash
php artisan horizon:install
php artisan horizon:publish
```
- Queue workers لمعالجة الـ LLM calls
- Failed jobs monitoring
- Metrics dashboard: `/horizon`

اضبط `config/horizon.php`:
```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['agent-actions', 'llm-calls', 'meta-webhooks'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

### 5.2 Queues

استخدم Queue للمهام الثقيلة عشان ما يوقفش الـ webhook:

```php
// مثال Job للـ LLM call
class ProcessLLMRequest implements ShouldQueue
{
    public function handle(SmartRouterInterface $router): void
    {
        $response = $router->chat($this->messages);
        // save response, send reply
    }
}

// dispatch
ProcessLLMRequest::dispatch($messages)->onQueue('llm-calls');
```

### 5.3 Caching
- `Cache::remember()` لنتائج التحليل المتكررة
- Redis للـ session + cache + queues

### 5.4 Monitoring
- Laravel Pulse للـ system monitoring
- Logs: daily files + Sentry للأخطاء
- Rate Limiting على كل webhook endpoint

### 5.5 Deployment Checklist

```bash
# 1. Production .env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# 2. Optimize
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. Queue workers
php artisan horizon

# 4. نصب وكيل HTTPS (nginx + letsencrypt / Cloudflare)
```

### 5.6 Meta Webhook Production

Meta webhooks **تتطلب HTTPS**. حلان:
1. **Cloudflare** (ي provie SSL مجاني)
2. **LetsEncrypt** مع nginx
3. **Ngrok** للتطوير المحلي

```bash
# للتطوير المحلي
ngrok http 8000
# استخدم الـ ngrok URL في Meta Webhook settings
```

---

## ملخص الملفات المطلوب إنشاؤها في كل Phase

### Phase 2 (WhatsApp + Email) — ~7 files
- `app/Domains/Integration/Platforms/WhatsAppPlatform.php`
- `app/Domains/Integration/Services/EmailService.php`
- `app/Domains/Agent/Agents/EmailAgent.php`
- `resources/views/emails/welcome.blade.php`
- `resources/views/emails/qualification.blade.php`
- `resources/views/emails/offer.blade.php`
- تحديث `WebhookController.php` (WhatsApp handler)

### Phase 3 (Dashboard) — ~20 files
Controllers:
- `Api/CampaignController.php`
- `Api/ConversationController.php`
- `Api/KnowledgeBaseController.php`
- `Api/AgentController.php`
- `Api/AnalyticsController.php`

Pages (React/Inertia):
- `resources/js/Pages/Leads/Index.tsx`
- `resources/js/Pages/Leads/Show.tsx`
- `resources/js/Pages/Campaigns/Index.tsx`
- `resources/js/Pages/Agent/Monitoring.tsx`
- `resources/js/Pages/Analytics/Index.tsx`
- `resources/js/Pages/KnowledgeBase/Index.tsx`
- `resources/js/Components/ConversationTimeline.tsx`

### Phase 4 (Testing) — ~15 files
- `tests/Unit/Domains/Lead/LeadRepositoryTest.php`
- `tests/Unit/Domains/Agent/CommentReplyAgentTest.php`
- `tests/Unit/Domains/Agent/LeadQualifierAgentTest.php`
- `tests/Unit/Domains/Integration/MetaPlatformTest.php`
- `tests/Feature/WebhookTest.php`
- `tests/Feature/LeadApiTest.php`
- `tests/Feature/AgentApiTest.php`
- `database/factories/LeadFactory.php`
- `database/factories/CampaignFactory.php`
- `database/factories/ConversationFactory.php`
- `database/factories/KnowledgeBaseFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `.github/workflows/tests.yml`

---

## ملفات التوثيق المطلوب استكمالها

| الملف | الحالة | المحتوى |
|---|---|---|
| `docs/README.md` | ✅ | Index |
| `docs/architecture.md` | ✅ | System architecture |
| `docs/database.md` | ✅ | Schema + ERD |
| `docs/api.md` | ✅ (محتاج إضافة) | أضف الـ Phase 3 endpoints |
| `docs/ai-providers.md` | ✅ | LLM providers |
| `docs/agents.md` | ✅ | Agent system |
| `docs/integrations.md` | ✅ | Meta setup |
| `docs/events.md` | ✅ | Event system |
| `docs/continuation.md` | ✅ (هذا الملف) | Future phases |
| `docs/testing.md` | ❌ | استراتيجية الاختبارات |
| `docs/deployment.md` | ❌ | نشر المشروع |
| `docs/security.md` | ❌ | الأمان |
| `docs/development.md` | ❌ | تشغيل المشروع محلياً |

---

## آخر حالة للمشروع

### ✅ تم إنجازه (Phase 0 + Phase 1)

**35+ ملف**:
```
app/Domains/Agent/          — 10 LLM Providers + Smart Router + Orchestrator + Agents
app/Domains/Campaign/       — Model, Repository, Enum, ServiceProvider
app/Domains/Lead/           — Model, Repository, Events, Enums, ServiceProvider
app/Domains/Conversation/   — Model, Repository, Events, Enums, ServiceProvider
app/Domains/Integration/    — SocialPlatformInterface + MetaPlatform + Webhook
app/Domains/KnowledgeBase/  — Model, Repository, ServiceProvider
app/Support/                — BaseRepository
app/Http/Controllers/       — WebhookController + Api/LeadController
app/Console/Commands/       — TestLLMProviders + TestMetaConnection
config/                     — agent.php + services.php
database/migrations/        — 8 tables
routes/                     — web.php + api.php + console.php
docs/                       — 8 documentation files
```

### 📊 Database: 8 tables
| campaigns | leads | lead_field_values | conversations |
|---|---|---|---|
| knowledge_base | integrations | agent_configs | agent_actions |

### 🤖 LLM Providers: 10
| OpenAI | Anthropic | Google | Groq | Ollama |
|---|---|---|---|---|
| OpenRouter | Zen (Big Pickle) | Mistral | DeepSeek | Cohere |

### 🔄 Agents: 2
| CommentReplyAgent | LeadQualifierAgent |
|---|---|
| يرُد على التعليقات + يبعت DM | يستخرج معلومات + يؤهل الـ Lead |

### 📦 Packages
| Laravel 13.9 | PostgreSQL | Redis | OpenAI PHP | Horizon |
|---|---|---|---|---|
| PHP 8.4 | pgvector | Inertia (Ready) | Tailwind (Ready) | React (Ready) |

---

**النصيحة:** أول حاجة تكملها هي:
1. **Phase 2 — WhatsApp** (لأن غالباً عملاء ميتا بيستخدمه)
2. **Phase 4 — Tests** (عشان تضمن إن اللي بنيته ميكسرش)
3. **Phase 3 — Dashboard** (عشان تتابع الشغل)

لو احتجت أي مساعدة في أي Phase، تقدر تفتح موضوع جديد وتشرح إيه المطلوب.
