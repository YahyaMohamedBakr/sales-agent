<?php

namespace App\Domains\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeBase extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $table = 'knowledge_base';

    protected $fillable = [
        'category',
        'title',
        'content',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): \Database\Factories\KnowledgeBaseFactory
    {
        return \Database\Factories\KnowledgeBaseFactory::new();
    }
}
