# Security Guide

## Encrypted Credentials

Integration credentials (Meta access tokens, API keys) are encrypted at rest using Laravel's built-in encryption:

```php
$integration->credentials = encrypt($token);
$token = decrypt($integration->credentials);
```

## Webhook Verification

All Meta webhooks are verified using the `hub_verify_token` challenge-response:

```php
if ($token !== config('services.meta.webhook_verify_token')) {
    abort(403);
}
```

## CSRF Protection

Webhook endpoints are excluded from CSRF protection in `routes/web.php`:

```php
Route::post('/webhook/meta', ...)->withoutMiddleware(VerifyCsrfToken::class);
```

## Rate Limiting

Apply rate limiting to webhook endpoints in `App\Http\Kernel` or `routes/web.php`:

```php
Route::post('/webhook/meta', ...)->middleware('throttle:60,1');
```

## Database Security

- **Soft Deletes** — no data is ever permanently deleted via the API
- **UUIDs** — no sequential IDs that can be guessed
- **Prepared Statements** — Eloquent everywhere prevents SQL injection

## AI Provider Keys

- Stored in `.env`, never in the database or code
- Use separate API keys for each environment
- Monitor usage via provider dashboards

## Production Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] HTTPS enabled (Cloudflare / LetsEncrypt)
- [ ] Webhook verify token is random
- [ ] Database backups configured
- [ ] Rate limiting on all webhooks
- [ ] Failed job monitoring (Horizon)
- [ ] Logs rotation configured
- [ ] PHP disabled functions: `exec`, `shell_exec`, `system`, `passthru`
