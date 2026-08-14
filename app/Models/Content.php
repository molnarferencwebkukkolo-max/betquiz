<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Content extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_id', 'type', 'title', 'slug', 'excerpt', 'content_json', 'content_html',
        'status', 'published_at', 'scheduled_for', 'effective_at', 'version',
        'seo_title', 'seo_description', 'canonical_url', 'robots_index', 'robots_follow',
        'sitemap_include', 'sitemap_priority', 'schema_type', 'social_title',
        'social_description', 'social_image_path', 'twitter_card', 'llms_include',
        'llms_summary', 'llms_section', 'llms_priority', 'markdown_enabled',
        'footer_visible', 'footer_label', 'footer_group', 'footer_order',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array', 'published_at' => 'datetime', 'scheduled_for' => 'datetime',
            'effective_at' => 'datetime', 'robots_index' => 'boolean', 'robots_follow' => 'boolean',
            'sitemap_include' => 'boolean', 'sitemap_priority' => 'float', 'llms_include' => 'boolean',
            'markdown_enabled' => 'boolean', 'footer_visible' => 'boolean', 'version' => 'integer',
            'llms_priority' => 'integer', 'footer_order' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class)->orderByDesc('version');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('status', 'published')
                ->orWhere(fn (Builder $query) => $query
                    ->where('status', 'scheduled')
                    ->whereNotNull('scheduled_for')
                    ->where('scheduled_for', '<=', now()));
        });
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === 'published'
            || ($this->status === 'scheduled' && $this->scheduled_for?->isPast());
    }

    public function publicUrl(): string
    {
        if ($this->type === 'article') {
            return route('articles.show', $this->slug);
        }

        return in_array($this->slug, ['aszf', 'adatkezeles', 'mediaajanlat'], true)
            ? url('/'.$this->slug)
            : route('content.show', $this->slug);
    }

    public function markdownUrl(): string
    {
        return $this->type === 'article'
            ? route('articles.markdown', $this->slug)
            : route('content.markdown', $this->slug);
    }

    public function effectiveSeoTitle(): string
    {
        return $this->seo_title ?: $this->title.' - KwizzGo';
    }

    public function effectiveSeoDescription(): string
    {
        return $this->seo_description ?: Str::limit(strip_tags($this->excerpt ?: $this->content_html), 160, '');
    }
}
