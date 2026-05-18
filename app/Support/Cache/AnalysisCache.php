<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

class AnalysisCache
{
    private const TTL = 3600;
    private const PREFIX = 'analysis:';

    public static function remember(string $text, string $language = 'ar'): ?array
    {
        $key = self::key($text, $language);

        return Cache::get($key);
    }

    public static function store(string $text, array $result, string $language = 'ar'): void
    {
        $key = self::key($text, $language);

        Cache::put($key, $result, self::TTL);
    }

    public static function forget(string $text, string $language = 'ar'): void
    {
        Cache::forget(self::key($text, $language));
    }

    private static function key(string $text, string $language): string
    {
        return self::PREFIX . md5($text) . ":{$language}";
    }
}
