<?php

namespace App\Domains\Agent\Enums;

enum ModelCapability: string
{
    case Chat = 'chat';
    case Analysis = 'analysis';
    case Embeddings = 'embeddings';
    case Vision = 'vision';
    case FunctionCalling = 'function_calling';
    case Streaming = 'streaming';
    case ArabicOptimized = 'arabic_optimized';
}
