# Meta (Facebook/Instagram) Integration

## Overview

```
Facebook Page ──► Meta Webhook ──► WebhookController ──► Orchestrator ──► Agents
                                            │
                              MetaPlatform (Graph API)
```

## Setup

### 1. Facebook App

1. Go to [developers.facebook.com](https://developers.facebook.com)
2. Create or use an existing App
3. Add **Webhooks** product
4. Add **Messenger** product

### 2. Page Access Token

```
Settings → Messenger → Page Access Token → Generate
```

### 3. App Secret

```
Settings → Basic → App Secret
```

### 4. Webhook Verify Token

Choose any string (e.g., `my_verify_token_2026`)

### 5. .env Configuration

```bash
META_APP_ID=123456789
META_APP_SECRET=abc123def456
META_PAGE_ID=123456789
META_PAGE_ACCESS_TOKEN=EAAx...
META_WEBHOOK_VERIFY_TOKEN=my_verify_token_2026
```

### 6. Configure Webhook on Meta

In your Facebook App → Webhooks → Page → Subscribe:

- **Callback URL:** `https://your-domain.com/webhook/meta`
- **Verify Token:** `my_verify_token_2026`
- **Subscription Fields:** `feed`, `messages`, `messaging_postbacks`

### 7. Page Subscription

Go to **Messenger → Settings → Webhooks → Edit Page Subscription**
Subscribe to: `messages`, `messaging_postbacks`

## Testing

```bash
# Test connection and page info
php artisan meta:test --action=info

# View comments on a post
php artisan meta:test --action=comments --post-id={post_id}

# Send a test message
php artisan meta:test --action=send-message --recipient={psid} --message="مرحباً!"
```

## Webhook Payload

### Comment Event
```json
{
  "type": "comment",
  "comment_id": "123_456",
  "post_id": "123_456",
  "message": "كم سعر المنتج؟",
  "from_id": "psid_123",
  "from_name": "أحمد",
  "is_reply": false,
  "created_time": 1700000000,
  "page_id": "123"
}
```

### Messenger Message
```json
{
  "type": "message",
  "sender_id": "psid_123",
  "message": "مرحباً",
  "is_echo": false,
  "timestamp": 1700000000,
  "page_id": "123"
}
```

## Flow: Comment → Reply → DM

```
1. User comments on ad: "كم السعر؟"
       │
2. Webhook received → Orchestrator.handleComment()
       │
3. CommentReplyAgent analyzes (LLM):
   - Intent: pricing_inquiry
   - Sentiment: 0.8
   - Suggested reply: "السعر ٣٠٠ ريال ..."
       │
4. Agent posts public reply on comment
       │
5. If needs follow-up → send DM via Messenger:
   "مرحباً {name}! شفت تعليقك .. 💬"
       │
6. User replies in Messenger
       │
7. LeadQualifierAgent engages:
   - Collects phone, email, city
   - Updates lead score
   - When score ≥ 70 → qualified!
```

## Troubleshooting

| Error | Cause | Solution |
|---|---|---|
| `Access token expired` | Token expired | Regenerate Page Access Token |
| `Rate limit hit` | Too many requests | Wait 5 min, reduce frequency |
| `(#100) ...` | Invalid params | Check Graph API version |
| `Webhook not verified` | Wrong verify token | Check META_WEBHOOK_VERIFY_TOKEN |
| `No websocket` | Server not accessible | Must use HTTPS, public URL |

## Security

- All credentials encrypted at rest (`Integration` model)
- Webhook signature verification (App Secret)
- Rate limiting on webhook endpoint
- Validate all incoming payloads
- Use `ngrok` for local testing
