<?php

namespace App\Domains\Integration\Enums;

enum IntegrationPlatform: string
{
    case Meta = 'meta';
    case WhatsApp = 'whatsapp';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Meta => 'فيسبوك / إنستجرام',
            self::WhatsApp => 'واتساب بيزنس',
            self::Email => 'البريد الإلكتروني',
        };
    }
}
