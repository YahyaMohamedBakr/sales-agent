<?php

namespace App\Domains\Agent\Enums;

enum ProviderStatus: string
{
    case Active = 'active';
    case RateLimited = 'rate_limited';
    case Unavailable = 'unavailable';
    case RequiresAuth = 'requires_auth';
    case NotConfigured = 'not_configured';
}
