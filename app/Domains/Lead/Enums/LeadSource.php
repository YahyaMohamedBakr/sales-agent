<?php

namespace App\Domains\Lead\Enums;

enum LeadSource: string
{
    case Comment = 'comment';
    case Messenger = 'messenger';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Manual = 'manual';
    case Webhook = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::Comment => 'تعليق فيسبوك',
            self::Messenger => 'مسنجر',
            self::WhatsApp => 'واتساب',
            self::Email => 'بريد إلكتروني',
            self::Manual => 'إدخال يدوي',
            self::Webhook => 'Webhook خارجي',
        };
    }
}
