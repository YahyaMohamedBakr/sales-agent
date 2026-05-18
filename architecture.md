# AI Sales Agent Architecture

## الهدف العام
وكيل ذكاء اصطناعي متصل بـ **Meta (Facebook/Instagram)**:
- يرد على تعليقات الإعلانات بشكل ذكي
- يلتقط معلومات التواصل من المتابعين
- يخزن البيانات ويؤهل الـ Leads
- يتواصل معاهم عبر **Messenger / WhatsApp / Email** لحد ما يجمع معلومات كافية

---

## 1. High-Level Architecture

```
                    ┌─────────────────────────────────────┐
                    │         META (FB/IG)                │
                    │  ┌──────────┐  ┌──────────┐        │
                    │  │  Ads     │  │ Messenger │        │
                    │  │ Comments │  │ / WhatsApp│        │
                    │  └────┬─────┘  └────┬──────┘        │
                    └───────┼──────────────┼──────────────┘
                            │              │
                    ┌───────▼──────────────▼──────────────┐
                    │         WEBHOOK SERVER              │
                    │   (FastAPI / NestJS)                │
                    │   يستقبل الأحداث من Meta            │
                    └───────┬─────────────────────────────┘
                            │
                    ┌───────▼─────────────────────────────┐
                    │         ORCHESTRATOR                │
                    │   (يقرر مين يتعامل مع الحدث)        │
                    └───────┬─────────────────────────────┘
                            │
          ┌─────────────────┼──────────────────┐
          ▼                 ▼                  ▼
   ┌────────────┐   ┌──────────────┐   ┌──────────────┐
   │ Comment    │   │ Lead         │   │ Messenger    │
   │ Handler    │   │ Qualifier    │   │ Agent        │
   └────────────┘   └──────────────┘   └──────────────┘
          │                 │                  │
          └─────────────────┼──────────────────┘
                            ▼
                    ┌────────────────┐
                    │   LLM Engine   │
                    │  (OpenAI/GPT)  │
                    │  + RAG (KB)    │
                    └───────┬────────┘
                            │
          ┌─────────────────┼──────────────────┐
          ▼                 ▼                  ▼
   ┌────────────┐   ┌──────────────┐   ┌──────────────┐
   │ PostgreSQL │   │   Redis      │   │   Vector DB  │
   │ (Leads,    │   │ (Sessions,   │   │ (Knowledge)  │
   │  Conv,     │   │  Rate Limit) │   │              │
   │  Campaigns)│   │              │   │              │
   └────────────┘   └──────────────┘   └──────────────┘
          │
          ▼
   ┌────────────────┐
   │   Dashboard    │
   │  (React Admin) │
   └────────────────┘
```

---

## 2. Meta Integration Details

### 2.1 Required Meta Permissions
| Permission | Purpose |
|---|---|
| `pages_manage_metadata` | قراءة بيانات الصفحة |
| `pages_read_engagement` | قراءة التعليقات على المنشورات |
| `pages_manage_engagement` | الرد على التعليقات |
| `pages_messaging` | إرسال واستقبال رسائل Messenger |
| `pages_manage_ads` | إدارة الإعلانات (اختياري) |
| `whatsapp_business_messaging` | واتساب بيزنس |

### 2.2 Webhooks من Meta
اشترك في الـ Webhooks التالية على مستوى الـ App:
- **`feed`** — للتعليقات الجديدة على المنشورات
- **`messages`** — لرسائل Messenger
- **`messaging_postbacks`** — للأزرار والتفاعلات

### 2.3 Comment Flow
```
1. User comments on ad ──► Meta sends webhook to our server
2. Server receives comment text + commenter name + page ID
3. Orchestrator يمرر للـ Comment Handler
4. Comment Handler:
   a. يحلل التعليق بالـ LLM (هل هو استفسار؟ طلب سعر؟ مجرد كلمة؟
   b. يولد رد مناسب
   c. ينشر الرد على التعليق عن طريق Graph API
   d. لو فيه طلب تواصل — يبعت رسالة خاصة عالـ Messenger
5. يتم تخزين الـ Lead في قاعدة البيانات
```

---

## 3. Database Schema (Core Tables)

```sql
-- الحملات الإعلانية
CREATE TABLE campaigns (
    id UUID PRIMARY KEY,
    meta_ad_id VARCHAR(255),        -- ID الإعلان على ميتا
    name VARCHAR(255),
    status VARCHAR(50),             -- active, paused, ended
    page_id VARCHAR(255),           -- الصفحة المرتبطة
    created_at TIMESTAMP DEFAULT NOW()
);

-- العملاء المحتملين
CREATE TABLE leads (
    id UUID PRIMARY KEY,
    psid VARCHAR(255) UNIQUE,       -- Meta Page-scoped ID (مستخدم Messenger)
    name VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    source VARCHAR(50),             -- comment, messenger, whatsapp
    campaign_id UUID REFERENCES campaigns,
    score INTEGER DEFAULT 0,        -- درجة التأهيل (0-100)
    status VARCHAR(50),             -- new, contacted, qualified, converted, lost
    notes TEXT,
    last_interaction TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- المحادثات
CREATE TABLE conversations (
    id UUID PRIMARY KEY,
    lead_id UUID REFERENCES leads,
    channel VARCHAR(50),            -- messenger, whatsapp, comment
    message TEXT,
    direction VARCHAR(10),          -- inbound, outbound
    metadata JSONB,                 -- intent, sentiment, extracted_data
    created_at TIMESTAMP DEFAULT NOW()
);

-- نقاط المعرفة (Product Info, Pricing, FAQs)
CREATE TABLE knowledge_base (
    id UUID PRIMARY KEY,
    category VARCHAR(100),          -- product, pricing, shipping, etc
    title TEXT,
    content TEXT,
    embedding VECTOR(1536),         -- للـ RAG search
    created_at TIMESTAMP DEFAULT NOW()
);

-- سجل الإجراءات التلقائية
CREATE TABLE agent_actions (
    id UUID PRIMARY KEY,
    lead_id UUID REFERENCES leads,
    action_type VARCHAR(50),        -- reply_comment, send_message, qualify, transfer
    prompt TEXT,
    response TEXT,
    model VARCHAR(50),
    tokens_used INTEGER,
    created_at TIMESTAMP DEFAULT NOW()
);
```

---

## 4. AI Agent Pipeline

### 4.1 Comment Analysis (LLM)
```
Input:  "كم سعر هذا المنتج؟"
Output: {
  "intent": "pricing_inquiry",
  "language": "ar",
  "needs_follow_up": true,
  "extracted_info": {},
  "suggested_reply": "سعر المنتج ٥٠٠ ريال. هل تحب نرسلك التفاصيل عالخاص؟ 📩",
  "should_dm": true
}
```

### 4.2 Follow-up Conversation (في Messenger)
الـ Agent بيستخدم **System Prompt** ثابت + **RAG** من Knowledge Base عشان يرد بدقة:
- يجاوب على أسئلة المنتجات والأسعار
- يطلب معلومات التواصل (رقم الهاتف - الإيميل - المدينة)
- يحدد الـ lead score بناءً على مدى التفاعل وجودة المعلومات
- لو احتاج — يسلم للبشر (Human Handoff)

### 4.3 Lead Scoring Criteria
| Criteria | Score |
|---|---|
| شارك رقم الهاتف | +30 |
| شارك الإيميل | +20 |
| سأل عن السعر أو المنتج | +15 |
| تفاعل في المحادثة (أكثر من 3 رسائل) | +15 |
| ذكر مدينة / موقع | +10 |
| رد على أسئلة التأهيل | +10 |
| **Total (Qualified)** | **70+** |

---

## 5. Tech Stack Recommendations

| Layer | Technology | Why |
|---|---|---|
| **Backend** | **Python FastAPI** | async, webhooks سهل، LLM integrations ممتازة |
| **AI/LLM** | **OpenAI GPT-4o** أو **Claude 3.5 Sonnet** | الأفضل في اللغات والعربية |
| **RAG / Vector DB** | **Qdrant** أو **pgvector** | بحث سريع في قاعدة المعرفة |
| **Database** | **PostgreSQL + pgvector** | كل حاجة في مكان واحد |
| **Cache** | **Redis** | session management, rate limiting |
| **Queue** | **Celery + Redis** | للمهام الخلفية (تحليل, إرسال) |
| **Meta SDK** | `facebook-business` (Python) | Graph API calls |
| **Messenger** | Meta Send API + Webhooks | تواصل مع العملاء |
| **Dashboard** | **React + Tailwind** | لوحة تحكم بسيطة |
| **Deployment** | **Docker + DigitalOcean / AWS** | استضافة |

---

## 6. Conversation Flow (Example)

```
1. تعليق على الإعلان: "عندي استفسار"
   ⟶ رد عام: "أهلاً! أرسلنا لك رسالة خاصة عالخاص 😊"
   ⟶ رسالة خاصة: "مرحباً! كيف أقدر أساعدك؟"

2. العميل يرد في Messenger: "عاوز سعر المنتج"
   ⟶ Agent: "السعر ٣٠٠ ريال. ممكن أعرف اسمك الأول؟"

3. العميل: "أحمد"
   ⟶ Agent: "أهلًا أحمد! طيب ممكن رقم واتساب عشان نرسلك التفاصيل؟"

4. العميل: "0551234567"
   ⟶ Agent: "شكراً أحمد! هنتواصل معاك قريب. عندك أي سؤال تاني؟"
   ⟶ Lead Score = 45 (مازال محتاج إيميل)

5. Agent يسأل: "هل تحب تستلم عروضنا على الإيميل؟"
   ⟶ العميل يشارك الإيميل ⟶ Lead Score = 65 ⟶ مؤهل!
```

---

## 7. Implementation Phases

### Phase 1 — الأساسيات (Week 1-2)
- [ ] هيكل المشروع (FastAPI + DB)
- [ ] الاتصال بـ Meta Graph API
- [ ] Webhook للتعليقات
- [ ] Comment reply automation basic (Simple replies)
- [ ] تخزين الـ Leads

### Phase 2 — الذكاء (Week 3-4)
- [ ] LLM Integration (OpenAI)
- [ ] Comment analysis + smart replies
- [ ] Messenger conversation agent
- [ ] RAG system + Knowledge Base
- [ ] Lead scoring

### Phase 3 — القنوات (Week 5-6)
- [ ] WhatsApp integration
- [ ] Email integration
- [ ] Human handoff
- [ ] Campaign tracking per ad

### Phase 4 — التحكم (Week 7-8)
- [ ] Dashboard (React)
- [ ] Analytics
- [ ] A/B testing for reply strategies
- [ ] Training interface (upload KB)

---

## 8. Security Considerations
- Store Meta access tokens encrypted
- Validate all webhooks (verify Meta signature)
- Rate limiting عشان ما ننحظر من Meta
- User data privacy compliance
- Logging all agent actions (للتدقيق)

---

## 9. Cost Estimation (Monthly)

| Item | Estimated Cost |
|---|---|
| **Server (Droplet/VPS)** | $20-40 |
| **OpenAI API** | $50-200 (حسب حجم المحادثات) |
| **PostgreSQL** | Included in VPS |
| **Redis** | Included in VPS |
| **Vector DB** | Free tier (Qdrant) |
| **Total** | **$70-240/month** |
