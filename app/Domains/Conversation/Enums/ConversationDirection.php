<?php

namespace App\Domains\Conversation\Enums;

enum ConversationDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
