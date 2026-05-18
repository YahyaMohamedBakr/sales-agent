<?php

namespace App\Domains\Agent\Enums;

enum AgentType: string
{
    case CommentReply = 'comment_reply';
    case LeadQualifier = 'lead_qualifier';
    case Support = 'support';
    case Outreach = 'outreach';
    case Voice = 'voice';

    public function label(): string
    {
        return match ($this) {
            self::CommentReply => 'الرد على التعليقات',
            self::LeadQualifier => 'تأهيل العملاء',
            self::Support => 'دعم ما بعد البيع',
            self::Outreach => 'حملات تواصل استباقية',
            self::Voice => 'مكالمات صوتية',
        };
    }
}
