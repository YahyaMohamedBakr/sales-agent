<?php

namespace App\Domains\Agent\Enums;

enum AIProvider: string
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Google = 'google';
    case Groq = 'groq';
    case Ollama = 'ollama';
    case OpenRouter = 'openrouter';
    case Mistral = 'mistral';
    case DeepSeek = 'deepseek';
    case Together = 'together';
    case Cohere = 'cohere';
    case Zen = 'zen';

    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI (GPT-4o, GPT-4o-mini, o1, o3-mini)',
            self::Anthropic => 'Anthropic (Claude 3.5 Sonnet, Claude 3 Haiku)',
            self::Google => 'Google (Gemini 1.5 Pro, Gemini 1.5 Flash, Gemini 2.0 Flash)',
            self::Groq => 'Groq (Llama 3, Mixtral) — سريع + فيه free tier',
            self::Ollama => 'Ollama (Llama 3, Qwen, DeepSeek) — محلي مجاني',
            self::OpenRouter => 'OpenRouter (مجمع لمئات الموديلز)',
            self::Mistral => 'Mistral AI (Mistral Large, Mistral Small)',
            self::DeepSeek => 'DeepSeek (DeepSeek V2, Coder)',
            self::Together => 'Together AI (Llama, DeepSeek, Qwen)',
            self::Cohere => 'Cohere (Command R+, Command R)',
            self::Zen => 'OpenCode Zen (Big Pickle, DeepSeek V4 Flash, Nemotron 3) — مجاني',
        };
    }

    public function defaultModel(): string
    {
        return match ($this) {
            self::OpenAI => 'gpt-4o',
            self::Anthropic => 'claude-3.5-sonnet',
            self::Google => 'gemini-1.5-pro',
            self::Groq => 'llama-3.1-70b-versatile',
            self::Ollama => 'llama3',
            self::OpenRouter => 'openai/gpt-4o',
            self::Mistral => 'mistral-large-latest',
            self::DeepSeek => 'deepseek-chat',
            self::Together => 'meta-llama/Meta-Llama-3.1-70B-Instruct-Turbo',
            self::Cohere => 'command-r-plus',
            self::Zen => 'big-pickle',
        };
    }

    public function tier(): ModelTier
    {
        return match ($this) {
            self::OpenAI => ModelTier::Premium,
            self::Anthropic => ModelTier::Premium,
            self::Google => ModelTier::Premium,
            self::Groq => ModelTier::Free,
            self::Ollama => ModelTier::Local,
            self::OpenRouter => ModelTier::Standard,
            self::Mistral => ModelTier::Premium,
            self::DeepSeek => ModelTier::Standard,
            self::Together => ModelTier::Standard,
            self::Cohere => ModelTier::Premium,
            self::Zen => ModelTier::Free,
        };
    }

    public function requiresApiKey(): bool
    {
        return match ($this) {
            self::Ollama => false,
            default => true,
        };
    }

    public function hasFreeTier(): bool
    {
        return match ($this) {
            self::Groq, self::Google => true,
            self::OpenRouter => true,
            self::Zen => true,
            default => false,
        };
    }

    /**
     * @return string[]
     */
    public function availableModels(): array
    {
        return match ($this) {
            self::OpenAI => [
                'gpt-4o',
                'gpt-4o-mini',
                'o1',
                'o3-mini',
            ],
            self::Anthropic => [
                'claude-3.5-sonnet',
                'claude-3-haiku',
                'claude-3-opus',
            ],
            self::Google => [
                'gemini-1.5-pro',
                'gemini-1.5-flash',
                'gemini-2.0-flash',
            ],
            self::Groq => [
                'llama-3.1-70b-versatile',
                'llama-3.1-8b-instant',
                'mixtral-8x7b-32768',
            ],
            self::Ollama => [
                'llama3',
                'llama3:70b',
                'qwen2.5',
                'deepseek-r1',
                'mistral',
            ],
            self::OpenRouter => [
                'openai/gpt-4o',
                'openai/gpt-4o-mini',
                'anthropic/claude-3.5-sonnet',
                'meta-llama/llama-3.1-70b-instruct',
                'google/gemini-1.5-flash',
            ],
            self::Mistral => [
                'mistral-large-latest',
                'mistral-small-latest',
                'codestral-latest',
            ],
            self::DeepSeek => [
                'deepseek-chat',
                'deepseek-coder',
            ],
            self::Together => [
                'meta-llama/Meta-Llama-3.1-70B-Instruct-Turbo',
                'meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo',
                'deepseek-ai/DeepSeek-V2',
            ],
            self::Cohere => [
                'command-r-plus',
                'command-r',
            ],
            self::Zen => [
                'big-pickle',
                'deepseek-v4-flash-free',
                'nemotron-3-super-free',
                'kimi-k2.5',
                'minimax-m2.5-free',
            ],
        };
    }
}
