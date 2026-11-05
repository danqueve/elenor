<?php

namespace App\Models;

use App\Support\ImageUrl;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

// Category URLs use the slug: /categoria/cables
#[RouteKey('slug')]
#[Fillable(['parent_id', 'name', 'slug', 'image', 'sort_order'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /** Heroicon shown when a category has no image, by slug. */
    private const ICONS = [
        'cables' => 'bolt',
        'proteccion-electrica' => 'shield-check',
        'mecanismos' => 'power',
        'iluminacion-led' => 'light-bulb',
        'canalizacion' => 'view-columns',
        'herramientas' => 'wrench-screwdriver',
    ];

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Top-level categories.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * Display order: the manual position first, then alphabetical.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * IDs of this category and of every category below it, at any depth.
     * A listing for a parent category must also show the products of its
     * subcategories.
     *
     * @return Collection<int, int>
     */
    public function descendantIds(): Collection
    {
        $rows = $this->getConnection()->select(
            <<<SQL
            WITH RECURSIVE tree (id) AS (
                SELECT id FROM {$this->getTable()} WHERE id = ?
                UNION ALL
                SELECT child.id FROM {$this->getTable()} AS child INNER JOIN tree ON child.parent_id = tree.id
            )
            SELECT id FROM tree
            SQL,
            [$this->getKey()],
        );

        return collect($rows)->pluck('id')->map(fn ($id) => (int) $id);
    }

    /**
     * The path from the top-level category down to this one (the breadcrumb).
     *
     * @return Collection<int, Category>
     */
    public function ancestorsAndSelf(): Collection
    {
        $table = $this->getTable();

        $rows = $this->getConnection()->select(
            <<<SQL
            WITH RECURSIVE chain (id, parent_id, depth) AS (
                SELECT id, parent_id, 0 FROM {$table} WHERE id = ?
                UNION ALL
                SELECT parent.id, parent.parent_id, chain.depth + 1
                FROM {$table} AS parent INNER JOIN chain ON parent.id = chain.parent_id
            )
            SELECT id FROM chain ORDER BY depth DESC
            SQL,
            [$this->getKey()],
        );

        $ids = collect($rows)->pluck('id')->map(fn ($id) => (int) $id);
        $categories = static::query()->whereKey($ids->all())->get()->keyBy('id');

        return $ids->map(fn (int $id) => $categories[$id]);
    }

    /**
     * The top-level category this one belongs to (itself when it is one).
     */
    public function rootId(): int
    {
        return $this->ancestorsAndSelf()->first()->getKey();
    }

    /**
     * Heroicon name for the tiles of categories without an image.
     */
    public function icon(): string
    {
        return self::ICONS[$this->slug] ?? 'squares-2x2';
    }

    public function imageUrl(): ?string
    {
        return ImageUrl::for($this->image);
    }
}
