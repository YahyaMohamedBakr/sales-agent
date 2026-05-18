# API Reference

## Webhooks

### Meta Webhook

#### Verify Webhook
```http
GET /webhook/meta?hub.mode=subscribe&hub.verify_token={token}&hub.challenge={challenge}
```

#### Receive Webhook Event
```http
POST /webhook/meta
Content-Type: application/json

{
  "object": "page",
  "entry": [{ ... }]
}
```

### WhatsApp Webhook

#### Verify WhatsApp Webhook
```http
GET /webhook/whatsapp?hub.mode=subscribe&hub.verify_token={token}&hub.challenge={challenge}
```

#### Receive WhatsApp Event
```http
POST /webhook/whatsapp
Content-Type: application/json

{
  "entry": [{ "changes": [{ "field": "messages", "value": { ... } }] }]
}
```

---

## Leads API

### List Leads
```http
GET /api/leads
```

Query params: `status`, `source`, `campaign_id`, `score_min`, `search`

### Get Lead
```http
GET /api/leads/{id}
```

Returns lead with campaign, conversations, field values.

### Create Lead
```http
POST /api/leads
Content-Type: application/json

{
  "psid": "123456789",
  "name": "أحمد",
  "phone": "0551234567",
  "email": "ahmed@example.com",
  "source": "comment",
  "campaign_id": "uuid-here",
  "metadata": { "city": "الرياض" }
}
```

### Update Lead
```http
PUT /api/leads/{id}

{
  "name": "أحمد المحدث",
  "status": "qualified",
  "score": 85
}
```

### Delete Lead
```http
DELETE /api/leads/{id}
```

### Add Lead Field Value
```http
POST /api/leads/{id}/fields

{
  "field_key": "city",
  "field_value": "الرياض"
}
```

---

## Conversations API

### List Lead Conversations
```http
GET /api/leads/{leadId}/conversations
```

### Log Message
```http
POST /api/leads/{leadId}/conversations

{
  "channel": "messenger",
  "message": "Hello!",
  "direction": "inbound",
  "metadata": {}
}
```

---

## Campaigns API

### List Campaigns
```http
GET /api/campaigns
```

Query params: `status`

### Create Campaign
```http
POST /api/campaigns

{
  "name": "حملة رمضان",
  "meta_ad_id": "ad_123",
  "status": "active",
  "platform": "facebook",
  "page_id": "page_456"
}
```

### Get Campaign
```http
GET /api/campaigns/{id}
```

### Update Campaign
```http
PUT /api/campaigns/{id}

{
  "name": "حملة محدثة",
  "status": "paused"
}
```

### Delete Campaign
```http
DELETE /api/campaigns/{id}
```

### Get Campaign Stats
```http
GET /api/campaigns/{id}/stats
```

Response:
```json
{
  "total_leads": 150,
  "qualified": 45,
  "converted": 12,
  "qualification_rate": 30.0,
  "conversion_rate": 8.0
}
```

---

## Knowledge Base API

### List Documents
```http
GET /api/knowledge-base
```

Query params: `category`, `search`

### Create Document
```http
POST /api/knowledge-base

{
  "category": "product",
  "title": "منتج أ",
  "content": "وصف المنتج...",
  "active": true
}
```

### Get Document
```http
GET /api/knowledge-base/{id}
```

### Update Document
```http
PUT /api/knowledge-base/{id}

{
  "title": "عنوان محدث",
  "content": "محتوى محدث"
}
```

### Delete Document
```http
DELETE /api/knowledge-base/{id}
```

### List Categories
```http
GET /api/knowledge-categories
```

Returns: `["product", "pricing", "shipping", ...]`

---

## Agent API

### Agent Health
```http
GET /api/agent/health
```

Returns list of available LLM providers with status.

### Full Health Report
```http
GET /api/agent/health/full
```

Response:
```json
{
  "available": [{ "name": "OpenAI", "status": "online", "model": "gpt-4o" }],
  "report": { ... }
}
```

### Send Message to Agent
```http
POST /api/agent/chat

{
  "message": "كم سعر المنتج؟",
  "provider": "smart",
  "strategy": "smart"
}
```

Response:
```json
{
  "success": true,
  "response": "...",
  "model": "gpt-4o",
  "provider": "openai",
  "tokens": 85,
  "time_ms": 1200
}
```

### Run Analysis
```http
POST /api/agent/analyze

{
  "text": "عاوز سعر المنتج",
  "provider": "smart"
}
```

---

## Analytics API

### Overview Stats
```http
GET /api/analytics/overview
```

```json
{
  "total_leads": 500,
  "qualified": 120,
  "converted": 35,
  "active": 300,
  "total_conversations": 1500,
  "total_campaigns": 10,
  "qualification_rate": 24.0,
  "conversion_rate": 7.0
}
```

### Leads by Source
```http
GET /api/analytics/leads-by-source
```

```json
[{ "source": "comment", "count": 200 }, { "source": "messenger", "count": 150 }]
```

### Leads by Day
```http
GET /api/analytics/leads-by-day?days=30
```

```json
[{ "date": "2025-01-01", "count": 15 }]
```

### Leads by Status
```http
GET /api/analytics/leads-by-status
```

```json
[{ "status": "new", "count": 100 }, { "status": "qualified", "count": 120 }]
```

### Top Campaigns
```http
GET /api/analytics/top-campaigns
```

```json
[{ "name": "Campaign A", "leads_count": 200, "qualified": 45 }]
```

### Agent Performance
```http
GET /api/analytics/agent-performance?days=7
```

```json
[{ "agent_type": "comment_reply", "action_type": "reply", "count": 50, "avg_tokens": 120, "avg_time_ms": 800 }]
```
