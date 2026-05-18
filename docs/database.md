# Database Schema

## Design Principles

- **UUID Primary Keys** — no auto-increment, safe for merging/distribution
- **JSONB metadata** — add fields without migrations
- **EAV via `lead_field_values`** — custom fields per lead
- **Soft Deletes** — all tables have `deleted_at`
- **Additive migrations only** — never drop/rename after first deploy

## ERD

```
┌─────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  campaigns  │1──*│     leads        │1──*│  conversations   │
└─────────────┘     └──────┬───────────┘     └──────────────────┘
                          │1                      │1
                          │                       │
                          │*                      │*
                    ┌─────▼──────────┐    ┌───────▼────────┐
                    │ lead_field_    │    │ agent_actions  │
                    │ values         │    └────────────────┘
                    └────────────────┘
```

## Tables

### `campaigns`

| Column | Type | Notes |
|---|---|---|
| id | UUID | Primary key |
| name | VARCHAR | Campaign name |
| meta_ad_id | VARCHAR | Facebook Ad ID (unique) |
| status | VARCHAR | draft, active, paused, completed, archived |
| platform | VARCHAR | facebook, instagram, etc |
| page_id | VARCHAR | Facebook page ID |
| metadata | JSONB | Any extra data |
| deleted_at | TIMESTAMP | Soft delete |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### `leads`

| Column | Type | Notes |
|---|---|---|
| id | UUID | Primary key |
| psid | VARCHAR | Facebook Page-scoped ID (unique) |
| name | VARCHAR | |
| phone | VARCHAR | |
| email | VARCHAR | |
| source | VARCHAR | comment, messenger, whatsapp, email, manual |
| campaign_id | UUID | FK → campaigns |
| score | INTEGER | 0-100 lead score |
| status | VARCHAR | new, contacted, qualifying, qualified, converted, lost |
| assigned_to | UUID | FK → users (null = unassigned) |
| metadata | JSONB | Any extra lead data |
| deleted_at | TIMESTAMP | |

### `lead_field_values` (EAV)

| Column | Type | Notes |
|---|---|---|
| id | UUID | |
| lead_id | UUID | FK → leads |
| field_key | VARCHAR | e.g. city, budget, interest |
| field_value | TEXT | |
| deleted_at | TIMESTAMP | |

Unique: `(lead_id, field_key)`

### `conversations`

| Column | Type | Notes |
|---|---|---|
| id | UUID | |
| lead_id | UUID | FK → leads |
| channel | VARCHAR | messenger, whatsapp, comment, email |
| message | TEXT | Message content |
| direction | VARCHAR | inbound, outbound |
| metadata | JSONB | intent, sentiment, extracted data |
| deleted_at | TIMESTAMP | |

### `knowledge_base`

| Column | Type | Notes |
|---|---|---|
| id | UUID | |
| category | VARCHAR | product, pricing, shipping, faq |
| title | TEXT | |
| content | TEXT | Document content |
| active | BOOLEAN | |
| deleted_at | TIMESTAMP | |

### `integrations`

| Column | Type | Notes |
|---|---|---|
| id | UUID | |
| platform | VARCHAR | meta, whatsapp, email |
| credentials | TEXT | Encrypted at rest |
| webhook_secret | TEXT | Encrypted |
| active | BOOLEAN | |
| metadata | JSONB | |
| deleted_at | TIMESTAMP | |

### `agent_configs`

| Column | Type | Notes |
|---|---|---|
| id | UUID | |
| agent_type | VARCHAR | comment_reply, lead_qualifier, support |
| name | VARCHAR | |
| config | JSONB | temperature, max_tokens, system_prompt, rules |
| active | BOOLEAN | |
| deleted_at | TIMESTAMP | |

### `agent_actions` (Audit Log)

| Column | Type | Notes |
|---|---|---|
| id | UUID | |
| lead_id | UUID | FK → leads |
| conversation_id | UUID | FK → conversations |
| agent_type | VARCHAR | Which agent acted |
| action_type | VARCHAR | analyze, reply, dm, qualify |
| prompt | TEXT | Full prompt sent to LLM |
| response | TEXT | Full LLM response |
| model_used | VARCHAR | e.g. gpt-4o, big-pickle |
| tokens_used | INTEGER | |
| processing_time_ms | INTEGER | |
| metadata | JSONB | |

## Adding a New Field

**Without migration:** add to `metadata` JSONB column
```php
$lead->update(['metadata->city' => 'الرياض']);
```

**With EAV:** add to `lead_field_values`
```php
$lead->fieldValues()->create(['field_key' => 'city', 'field_value' => 'الرياض']);
```

**With migration (when field is needed for queries):**
```php
Schema::table('leads', function (Blueprint $table) {
    $table->string('city')->nullable()->after('email');
});
```
Always `nullable` + `after()` for additive safety.

## Indexes

Foreign keys and frequently queried columns are indexed automatically by:
- Laravel `foreignUuid()` / `constrained()`
- `unique()` constraints on psid, meta_ad_id, (lead_id, field_key)

For PostgreSQL performance, add:
```sql
CREATE INDEX CONCURRENTLY idx_leads_score ON leads (score) WHERE deleted_at IS NULL;
CREATE INDEX CONCURRENTLY idx_leads_status ON leads (status) WHERE deleted_at IS NULL;
CREATE INDEX CONCURRENTLY idx_conversations_lead ON conversations (lead_id);
```
