<?php

namespace App\Domains\Agent\Enums;

enum ModelTier: string
{
    case Premium = 'premium';       // GPT-4o, Claude 3.5 Sonnet, Gemini 1.5 Pro
    case Standard = 'standard';     // GPT-4o-mini, Claude 3 Haiku, Gemini 1.5 Flash
    case Free = 'free';             // Groq Llama 3, Gemini Free
    case Local = 'local';           // Ollama models
}
