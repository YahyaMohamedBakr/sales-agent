<?php

namespace App\Domains\Agent\Enums;

enum RouterStrategy: string
{
    case Smart = 'smart';           // التلقائي الذكي
    case Priority = 'priority';     // حسب الأولوية
    case CostOptimized = 'cost_optimized';  // الأرخص أولاً
    case Performance = 'performance';       // الأسرع أولاً
}
