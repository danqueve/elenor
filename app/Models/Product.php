<?php

namespace App\Models;

use App\Enums\ProductUnit;
use App\Support\ImageUrl;
use App\Support\PriceDisplay;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Product URLs use the slug: /producto/cable-unipolar-2-5-mm-celeste-prysmian
#[RouteKey('slug')]
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
    'compare_at_price',
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
            'compare_at_price' => 'decimal:2',
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

    /**
     * Products whose previous price is higher than the current one.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function onSale(Builder $query): void
    {
        $query->whereNotNull('products.compare_at_price')
            ->whereColumn('products.compare_at_price', '>', 'products.list_price');
    }

    /**
     * Adds the path of the first photo as "first_image", in the same query
     * (no extra query per product in a listing). Read it with coverImageUrl().
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function withFirstImage(Builder $query): void
    {
        $query->addSelect(['first_image' => ProductImage::query()
            ->select('path')
            ->whereColumn('product_images.product_id', 'products.id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(1),
        ]);
    }

    public function isInStock(): bool
    {
        return bccomp($this->stock, '0', 2) > 0;
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null
            && bccomp($this->compare_at_price, $this->list_price, 2) > 0;
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

    /**
     * What to show for the price: the price this visitor pays, and for an
     * offer the previous price struck through with the discount. Professionals
     * see their own price and no discount (it was calculated on the list price).
     */
    public function priceDisplayFor(?User $user): PriceDisplay
    {
        $price = $this->priceFor($user);

        if ($price !== $this->list_price) {
            return new PriceDisplay($price, isProfessional: true);
        }

        if (! $this->isOnSale()) {
            return new PriceDisplay($price);
        }

        $discount = bcsub($this->compare_at_price, $price, 4);
        $percent = (int) round((float) bcdiv(bcmul($discount, '100', 4), $this->compare_at_price, 4));

        return new PriceDisplay($price, $this->compare_at_price, max($percent, 1));
    }

    /**
     * The first photo, or null. Works with products loaded with
     * withFirstImage() or with their "images" relation loaded.
     */
    public function coverImageUrl(): ?string
    {
        $attributes = $this->getAttributes();

        if (array_key_exists('first_image', $attributes)) {
            return ImageUrl::for($attributes['first_image']);
        }

        return $this->relationLoaded('images') ? $this->images->first()?->url() : null;
    }
}
