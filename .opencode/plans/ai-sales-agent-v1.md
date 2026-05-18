# AI Sales Agent — خطة التنفيذ الكاملة (Laravel)

## 1. هيكل المشروع (Phase 0 — تم)

### Domains
```
app/Domains/
├── Campaign/     الحملات الإعلانية
├── Lead/         العملاء المحتملين + EAV fields
├── Conversation/ المحادثات
├── Agent/        الذكاء الاصطناعي (AI Providers + Agents)
├── Integration/  ربط ميتا, واتساب, إيميل
├── KnowledgeBase/ قاعدة المعرفة (RAG)
└── Dashboard/    لوحة التحكم

app/Support/
├── ValueObjects/
└── Helpers/
```

### Packages (تم تثبيتها)
- `openai-php/laravel` — OpenAI
- `laravel/horizon` — Queue monitoring

### Migrations
| الجدول | الميزات |
|---|---|
| campaigns | UUID, meta_ad_id, status, platform, JSONB metadata |
| leads | UUID, psid, score, status, JSONB metadata |
| lead_field_values | EAV (lead_id + field_key unique) |
| conversations | UUID, channel, direction, JSONB metadata |
| knowledge_base | UUID, category, title, content |
| integrations | UUID, platform, credentials (encrypted) |
| agent_configs | UUID, agent_type, name, JSONB config |
| agent_actions | UUID, prompt, response, model_used, tokens, timing |

---

## 2. قاعدة البيانات (Additive-Only Design)

### المبادئ:
- **UUID** بدل auto-increment
- **JSONB** metadata — تضيف حقل بدون migration
- **EAV** — lead_field_values لحقول مخصصة
- **Soft Deletes** كل الجداول
- **مفيش down() بعد أول deploy** — الميجراشن adding only

### ERD:
```
campaigns 1──* leads 1──* conversations
            leads 1──* lead_field_values
            leads 1──* agent_actions
            conversations 1──* agent_actions
```

---

## 3. Design Patterns

### Strategy — AI Providers
```php
interface AIProviderInterface {
    public function chat(array $messages, AgentConfig $config): AIResponse;
    public function analyze(string $text): AnalysisResult;
    public function embed(string $text): array;
}
```

### Strategy — Social Platforms
```php
interface SocialPlatformInterface {
    public function getComments(string $postId): Collection;
    public function replyToComment(string $commentId, string $message): void;
    public function sendMessage(string $recipientId, string $message): MessageResult;
}
```

### Chain of Responsibility — Agent Pipeline
```
Inbound → FilterAgent → AnalyzeAgent → RouteAgent → ActionAgent → LogAgent
```

### Repository Pattern
```php
interface LeadRepositoryInterface {
    public function findByPsid(string $psid): ?Lead;
    public function findQualified(int $minScore = 70): Collection;
    public function save(Lead $lead): Lead;
}
```

### Event-Driven
- `LeadCreated` → SendWelcome, SyncToCRM
- `LeadCommented` → AnalyzeIntent, CalculateScore
- `LeadQualified` → NotifyHuman, AssignAgent
- `MessageReceived` → RouteToAgent, LogConversation

---

## 4. الملفات المطلوب إنشاؤها

### Migrations (8 files)
- [ ] create_campaigns_table
- [ ] create_leads_table
- [ ] create_lead_field_values_table
- [ ] create_conversations_table
- [ ] create_knowledge_base_table
- [ ] create_integrations_table
- [ ] create_agent_configs_table
- [ ] create_agent_actions_table

### Eloquent Models (8 files)
- Campaign, Lead, LeadFieldValue, Conversation, KnowledgeBase, Integration, AgentConfig, AgentAction

### Enums (8 files)
- CampaignStatus, LeadSource, LeadStatus, ConversationChannel, ConversationDirection, AgentType, IntegrationPlatform, LeadScoreEvent

### DTOs (5 files)
- CommentData, MessageData, AnalysisResult, AIResponse, LeadQualificationData

### Contracts (4 files)
- AIProviderInterface, SocialPlatformInterface, AgentInterface, BaseRepositoryInterface

### Service Providers (8 files)
- CampaignServiceProvider, LeadServiceProvider, ConversationServiceProvider, AgentServiceProvider, IntegrationServiceProvider, KnowledgeBaseServiceProvider, DashboardServiceProvider, AIServiceProvider

### Platform Implementations (3 files)
- MetaPlatform, WhatsAppPlatform, EmailPlatform

### AI Providers (3 files)
- OpenAIProvider, ClaudeProvider, OllamaProvider

### Agents (4 files)
- CommentReplyAgent, LeadQualifierAgent, SupportAgent, Orchestrator

### Actions (10+ files)
- Campaign: CreateCampaign, ActivateCampaign
- Lead: CreateLead, QualifyLead, CalculateScore, MergeLead
- Conversation: LogMessage, AnalyzeMessage
- Integration: ConnectPlatform
- KnowledgeBase: SearchDocument

### Controllers (4 files)
- WebhookController, LeadController, DashboardController, KnowledgeBaseController

### Config (2 files)
- config/agent.php, config/integrations.php

---

## 5. التوثيق (docs/)

| الملف | المحتوى |
|---|---|
| docs/README.md | فهرس التوثيق |
| docs/architecture.md | System Architecture + diagrams |
| docs/database.md | ERD, كل جدول + columns |
| docs/api.md | API endpoints |
| docs/agents.md | دليل إضافة Agent جديد |
| docs/ai-providers.md | دليل إضافة AI Provider |
| docs/integrations.md | ربط ميتا, واتساب, إيميل |
| docs/events.md | الأحداث + الـ Listeners |
| docs/testing.md | استراتيجية الاختبارات |
| docs/development.md | تشغيل المشروع محلياً |
| docs/deployment.md | نشر المشروع |
| docs/security.md | الأمان |

---

## 6. مراحل التنفيذ

### Phase 0 (الأساسيات) — جاري
- [x] Laravel 13 installation
- [x] Directory structure
- [x] Packages (openai, horizon)
- [ ] Migrations + Models + Enums
- [ ] DTOs + Contracts
- [ ] Service Providers
- [ ] Config files
- [ ] BaseRepository
- [ ] Documentation

### Phase 1 (ميتا)
- [ ] MetaPlatform class + WebhookController
- [ ] Integration Model (encrypted tokens)
- [ ] CommentReplyAgent (basic + LLM)
- [ ] Lead creation & qualification

### Phase 2 (القنوات)
- [ ] WhatsApp + Email platforms
- [ ] Human handoff
- [ ] Agent Orchestrator

### Phase 3 (التحكم)
- [ ] Dashboard + Analytics
- [ ] Full testing suite

---

## 7. أمان

- `Integration.credentials` مشفرة (Laravel encrypt)
- Webhook verification (X-Hub-Signature)
- Rate limiting على الـ webhooks
- Soft deletes (لا حذف فعلي)
- استثناء webhooks من CSRF
- Eloquent queries everywhere
