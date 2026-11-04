<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['parent_id', 'name', 'slug', 'image', 'sort_order'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

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
}
