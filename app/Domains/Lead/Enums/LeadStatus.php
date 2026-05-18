<?php

namespace App\Domains\Lead\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualifying = 'qualifying';
    case Qualified = 'qualified';
    case Converted = 'converted';
    case Lost = 'lost';
    case Escalated = 'escalated';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Contacted => 'تم التواصل',
            self::Qualifying => 'قيد التأهيل',
            self::Qualified => 'مؤهل',
            self::Converted => 'تم التحويل',
            self::Lost => 'منصرف',
            self::Escalated => 'محول للبشر',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::New, self::Contacted, self::Qualifying, self::Qualified, self::Escalated]);
    }
}
