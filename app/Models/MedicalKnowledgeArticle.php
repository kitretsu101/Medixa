<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'topic',
    'language',
    'content',
    'source_url',
    'source_name',
    'review_date',
    'reviewer_name',
    'approval_status',
    'version',
    'is_active',
])]
class MedicalKnowledgeArticle extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    /**
     * Articles a named reviewer approved, that are still inside the review window.
     *
     * @param  Builder<MedicalKnowledgeArticle>  $query
     * @return Builder<MedicalKnowledgeArticle>
     */
    #[Scope]
    protected function retrievable(Builder $query): Builder
    {
        $validDays = max(1, (int) config('medixa.knowledge.review_valid_days', 365));

        return $query
            ->where('approval_status', 'approved')
            ->where('is_active', true)
            ->whereNotNull('reviewer_name')
            ->where('reviewer_name', '!=', '')
            ->whereNotNull('review_date')
            ->whereDate('review_date', '>=', now()->subDays($validDays)->toDateString());
    }
}
