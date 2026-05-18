# Event System

## Architecture

```
Agent Action → dispatch(Event) → 1..* Listeners (sync or queued)
```

Events decouple agent actions from side effects. Adding a new feature = adding a new Listener only.

## Events

| Event | Payload | Fired When | Typical Listeners |
|---|---|---|---|
| `LeadCreated` | lead, source, message | Lead created via webhook or manually | CalculateInitialScore |
| `LeadCommented` | lead, commentId, commentText, reply | Agent replied to a comment | Log, Analytics |
| `LeadQualified` | lead, score, criteria | Lead score reaches 70+ | NotifyHuman, AssignAgent |
| `LeadConverted` | lead, notes | Lead converted to customer | SyncToCRM |
| `MessageReceived` | lead, conversation, channel, message | New message from lead | RouteToAgent, LogConversation |

## Usage

```php
use App\Domains\Lead\Events\LeadCreated;

// Dispatch
LeadCreated::dispatch($lead, 'comment', 'Message text here');

// Or via event helper
event(new LeadCreated($lead, 'messenger'));
```

## Adding a New Listener

### 1. Create listener class

```php
<?php

namespace App\Domains\Lead\Listeners;

use App\Domains\Lead\Events\LeadCreated;
use Illuminate\Support\Facades\Log;

class SyncLeadToExternalCRM
{
    public function handle(LeadCreated $event): void
    {
        // Send lead to external CRM API
        Http::post('https://crm.example.com/api/leads', [
            'name' => $event->lead->name,
            'phone' => $event->lead->phone,
            'email' => $event->lead->email,
        ]);
    }
}
```

### 2. Register in EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    LeadCreated::class => [
        CalculateInitialScore::class,
        SyncLeadToExternalCRM::class,  // ← add here
    ],
];
```

### 3. For heavy work, queue the listener

```php
class SyncLeadToExternalCRM implements ShouldQueue
{
    public $connection = 'redis';
    public $queue = 'crm-sync';
    
    public function handle(LeadCreated $event): void { ... }
}
```

## Testing Events

```php
use Illuminate\Support\Facades\Event;
use App\Domains\Lead\Events\LeadCreated;

test('lead creation dispatches event', function () {
    Event::fake();
    
    // ... action that creates lead
    
    Event::assertDispatched(LeadCreated::class);
    Event::assertDispatched(LeadCreated::class, function ($event) {
        return $event->source === 'comment';
    });
});
```
