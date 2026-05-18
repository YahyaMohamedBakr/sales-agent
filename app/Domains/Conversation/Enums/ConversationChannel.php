<?php

namespace App\Domains\Conversation\Enums;

enum ConversationChannel: string
{
    case Messenger = 'messenger';
    case WhatsApp = 'whatsapp';
    case Comment = 'comment';
    case Email = 'email';
    case Webchat = 'webchat';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Messenger => 'فيسبوك مسنجر',
            self::WhatsApp => 'واتساب',
            self::Comment => 'تعليق',
            self::Email => 'بريد إلكتروني',
            self::Webchat => 'شات الموقع',
            self::Phone => 'مكالمة هاتفية',
        };
    }
}
