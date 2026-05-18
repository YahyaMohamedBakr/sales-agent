<?php

namespace App\Domains\Setting\Services;

use App\Domains\Setting\Models\Setting;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    private static ?array $cache = null;

    public function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        static::$cache = Setting::pluck('value', 'key')->toArray();

        return static::$cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, string $value, string $group = 'general', string $type = 'string'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $value, 'type' => $type],
        );

        static::$cache = null;
    }

    public function delete(string $key): void
    {
        Setting::where('key', $key)->delete();
        static::$cache = null;
    }

    public function getByGroup(string $group): array
    {
        return Setting::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public function mergeIntoLaravelConfig(): void
    {
        $map = [
            // AI Providers
            'OPENAI_API_KEY' => ['config' => 'agent.providers.openai.api_key', 'group' => 'openai'],
            'OPENAI_BASE_URL' => ['config' => 'agent.providers.openai.base_url', 'group' => 'openai'],
            'ANTHROPIC_API_KEY' => ['config' => 'agent.providers.anthropic.api_key', 'group' => 'anthropic'],
            'GOOGLE_API_KEY' => ['config' => 'agent.providers.google.api_key', 'group' => 'google'],
            'GROQ_API_KEY' => ['config' => 'agent.providers.groq.api_key', 'group' => 'groq'],
            'MISTRAL_API_KEY' => ['config' => 'agent.providers.mistral.api_key', 'group' => 'mistral'],
            'DEEPSEEK_API_KEY' => ['config' => 'agent.providers.deepseek.api_key', 'group' => 'deepseek'],
            'TOGETHER_API_KEY' => ['config' => 'agent.providers.together.api_key', 'group' => 'together'],
            'COHERE_API_KEY' => ['config' => 'agent.providers.cohere.api_key', 'group' => 'cohere'],
            'ZEN_API_KEY' => ['config' => 'agent.providers.zen.api_key', 'group' => 'zen'],
            'OLLAMA_URL' => ['config' => 'agent.providers.ollama.base_url', 'group' => 'ollama'],
            'OLLAMA_API_KEY' => ['config' => 'agent.providers.ollama.api_key', 'group' => 'ollama'],

            // Meta
            'META_APP_ID' => ['config' => 'services.meta.app_id', 'group' => 'meta'],
            'META_APP_SECRET' => ['config' => 'services.meta.app_secret', 'group' => 'meta'],
            'META_PAGE_ID' => ['config' => 'services.meta.page_id', 'group' => 'meta'],
            'META_PAGE_ACCESS_TOKEN' => ['config' => 'services.meta.page_access_token', 'group' => 'meta'],
            'META_WEBHOOK_VERIFY_TOKEN' => ['config' => 'services.meta.webhook_verify_token', 'group' => 'meta'],

            // WhatsApp
            'WHATSAPP_PHONE_NUMBER_ID' => ['config' => 'services.whatsapp.phone_number_id', 'group' => 'whatsapp'],
            'WHATSAPP_ACCESS_TOKEN' => ['config' => 'services.whatsapp.access_token', 'group' => 'whatsapp'],

            // Email (SendGrid)
            'MAIL_HOST' => ['config' => 'mail.mailers.smtp.host', 'group' => 'email'],
            'MAIL_PORT' => ['config' => 'mail.mailers.smtp.port', 'group' => 'email'],
            'MAIL_USERNAME' => ['config' => 'mail.mailers.smtp.username', 'group' => 'email'],
            'MAIL_PASSWORD' => ['config' => 'mail.mailers.smtp.password', 'group' => 'email'],
            'MAIL_FROM_ADDRESS' => ['config' => 'mail.from.address', 'group' => 'email'],
            'MAIL_FROM_NAME' => ['config' => 'mail.from.name', 'group' => 'email'],
        ];

        $settings = $this->all();
        if (empty($settings)) return;

        foreach ($map as $key => $cfg) {
            if (isset($settings[$key]) && $settings[$key] !== '') {
                $keys = explode('.', $cfg['config']);
                config([$cfg['config'] => $settings[$key]]);
            }
        }

        Log::debug('SettingsService: merged DB settings into config');
    }
}
