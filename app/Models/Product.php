<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sku',
    'manufacturer_code',
    'name',
    'slug',
    'description',
    'category_id',
    'brand_id',
    'unit',
    'list_price',
    'professional_price',
    'stock',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast. Decimals come back as strings
     * ("1234.50") on purpose: money is never handled as a float.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'list_price' => 'decimal:2',
            'professional_price' => 'decimal:2',
            'stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Technical sheet: the name/value pairs that also feed the catalog filters,
     * in the order they were entered. (Not called "attributes": that name
     * belongs to Eloquent itself.)
     *
     * @return HasMany<ProductAttribute, $this>
     */
    public function specs(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('id');
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->where('stock', '>', 0);
    }

    public function isInStock(): bool
    {
        return bccomp($this->stock, '0', 2) > 0;
    }

    /**
     * Unit price this customer pays. Decided on the server, never taken from
     * the browser: approved professional and company accounts get the
     * professional price when the product has one.
     */
    public function priceFor(?User $user): string
    {
        if ($this->professional_price !== null && $user?->hasProfessionalPricing()) {
            return $this->professional_price;
        }

        return $this->list_price;
    }
}
