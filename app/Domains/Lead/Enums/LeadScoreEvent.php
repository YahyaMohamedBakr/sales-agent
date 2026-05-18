<?php

namespace App\Domains\Lead\Enums;

enum LeadScoreEvent: string
{
    case PhoneShared = 'phone_shared';
    case EmailShared = 'email_shared';
    case Interacted = 'interacted';
    case AskedPrice = 'asked_price';
    case AskedProduct = 'asked_product';
    case SharedCity = 'shared_city';
    case RepliedQualifying = 'replied_qualifying';
    case MultipleMessages = 'multiple_messages';
    case ClickedLink = 'clicked_link';

    public function points(): int
    {
        return match ($this) {
            self::PhoneShared => 30,
            self::EmailShared => 20,
            self::AskedPrice => 15,
            self::Interacted => 15,
            self::MultipleMessages => 15,
            self::AskedProduct => 10,
            self::SharedCity => 10,
            self::RepliedQualifying => 10,
            self::ClickedLink => 5,
        };
    }

    public static function threshold(): int
    {
        return 70;
    }
}
