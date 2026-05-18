<?php

namespace App\Domains\Agent\DTOs;

class AnalysisResult
{
    public function __construct(
        public readonly string $intent,
        public readonly string $language,
        public readonly float $sentiment,
        public readonly array $entities = [],
        public readonly int $score = 0,
        public readonly ?string $suggestedReply = null,
        public readonly bool $needsFollowUp = false,
        public readonly bool $shouldDM = false,
        public readonly array $extractedInfo = [],
        public readonly ?string $error = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            intent: $data['intent'] ?? 'unknown',
            language: $data['language'] ?? 'ar',
            sentiment: $data['sentiment'] ?? 0.0,
            entities: $data['entities'] ?? [],
            score: $data['score'] ?? 0,
            suggestedReply: $data['suggested_reply'] ?? null,
            needsFollowUp: $data['needs_follow_up'] ?? false,
            shouldDM: $data['should_dm'] ?? false,
            extractedInfo: $data['extracted_info'] ?? [],
            error: $data['error'] ?? null,
        );
    }
}
