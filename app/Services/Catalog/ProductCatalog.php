<?php

namespace App\Services\Catalog;

use App\Enums\CatalogSort;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the public catalog asks the database: listings, searches,
 * filters and the options (facets) offered to narrow them down.
 *
 * Only active products are ever returned. Prices are the ones the visitor
 * pays (professional accounts filter and sort by their own price).
 */
class ProductCatalog
{
    /**
     * Products that match the filters, in the requested order.
     *
     * @return Builder<Product>
     */
    public function products(ProductFilters $filters): Builder
    {
        $query = $this->query($filters);

        $this->sort($query, $filters);

        // A total order, so pagination never repeats or skips a product.
        return $query->orderBy('products.id');
    }

    /**
     * Brands of the matching products, with how many products each would give.
     * The brand filter itself is ignored, so several brands can be combined.
     *
     * @return Collection<int, object{id: int, name: string, total: int}>
     */
    public function brandFacet(ProductFilters $filters): Collection
    {
        $brands = $this->query($filters, skip: ['brand'])
            ->toBase()
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->groupBy('brands.id', 'brands.name')
            ->orderBy('brands.name')
            ->get(['brands.id', 'brands.name', DB::raw('COUNT(products.id) as total')])
            ->map(fn ($row) => (object) ['id' => (int) $row->id, 'name' => $row->name, 'total' => (int) $row->total]);

        // A selected brand that no longer gives results stays listed, so it can be unticked.
        $missing = array_diff($filters->brandIds, $brands->pluck('id')->all());

        if ($missing !== []) {
            $extra = Brand::query()->whereKey($missing)->get()
                ->map(fn (Brand $brand) => (object) ['id' => $brand->id, 'name' => $brand->name, 'total' => 0]);

            $brands = $brands->concat($extra)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        return $brands;
    }

    /**
     * The attributes offered as filters, each with its values and how many
     * products each value would give.
     *
     * Which attributes appear depends only on the category and the search, not
     * on the other filters, so the groups don't jump around while filtering.
     * The counts take every other filter into account (but not the group's
     * own selection, so several values can be combined).
     *
     * @return list<array{name: string, values: list<array{value: string, total: int}>}>
     */
    public function specFacets(ProductFilters $filters): array
    {
        $scope = $this->query(new ProductFilters(
            category: $filters->category,
            search: $filters->search,
            onlyOffers: $filters->onlyOffers,
            user: $filters->user,
        ))->select('products.id');

        $names = ProductAttribute::query()
            ->whereIn('product_id', $scope)
            ->groupBy('name')
            ->havingRaw('COUNT(DISTINCT value) >= 2')
            ->orderByRaw('COUNT(*) DESC')
            ->orderBy('name')
            ->pluck('name')
            ->merge(array_keys($filters->specs))
            ->unique()
            ->values();

        $facets = [];

        foreach ($names as $name) {
            $totals = ProductAttribute::query()
                ->toBase()
                ->where('name', $name)
                ->whereIn('product_id', $this->query($filters, skip: ['spec:'.$name])->select('products.id'))
                ->groupBy('value')
                ->selectRaw('value, COUNT(*) as total')
                ->pluck('total', 'value')
                ->map(fn ($total) => (int) $total);

            foreach ($filters->specs[$name] ?? [] as $selected) {
                if (! $totals->has($selected)) {
                    $totals->put($selected, 0);
                }
            }

            $facets[] = [
                'name' => $name,
                'values' => $totals
                    ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
                    ->map(fn (int $total, int|string $value) => ['value' => (string) $value, 'total' => $total])
                    ->values()
                    ->all(),
            ];
        }

        return $facets;
    }

    /**
     * The cheapest and the most expensive matching product (whole pesos),
     * ignoring the price filter itself. Null when nothing matches.
     *
     * @return array{min: int, max: int}|null
     */
    public function priceBounds(ProductFilters $filters): ?array
    {
        $price = $this->priceExpression($filters->user);

        $row = $this->query($filters, skip: ['price'])
            ->toBase()
            ->selectRaw("MIN({$price}) as lowest, MAX({$price}) as highest")
            ->first();

        if ($row === null || $row->lowest === null) {
            return null;
        }

        return ['min' => (int) floor((float) $row->lowest), 'max' => (int) ceil((float) $row->highest)];
    }

    /**
     * The products for the search box dropdown: the best matches first.
     * Two characters at least, otherwise nothing.
     *
     * @return Collection<int, Product>
     */
    public function suggestions(string $search, ?User $user, int $limit = 6): Collection
    {
        $search = trim($search);

        if (mb_strlen($search) < 2) {
            return new Collection;
        }

        return $this->products(new ProductFilters(search: $search, sort: CatalogSort::Relevance, user: $user))
            ->with('brand')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  list<string>  $skip  Filters to leave out: "brand", "price" or "spec:<name>".
     * @return Builder<Product>
     */
    private function query(ProductFilters $filters, array $skip = []): Builder
    {
        $query = Product::query()->active();

        if ($filters->category !== null) {
            $query->whereIn('products.category_id', $filters->category->descendantIds());
        }

        if ($filters->onlyOffers) {
            $query->onSale();
        }

        if ($filters->search !== '') {
            $this->search($query, $filters->search);
        }

        if ($filters->brandIds !== [] && ! in_array('brand', $skip, true)) {
            $query->whereIn('products.brand_id', $filters->brandIds);
        }

        if (! in_array('price', $skip, true)) {
            $price = $this->priceExpression($filters->user);

            if ($filters->minPrice !== null) {
                $query->whereRaw("{$price} >= ?", [$filters->minPrice]);
            }

            if ($filters->maxPrice !== null) {
                $query->whereRaw("{$price} <= ?", [$filters->maxPrice]);
            }
        }

        // Several values of one attribute are alternatives (OR); different attributes all have to match (AND).
        foreach ($filters->specs as $name => $values) {
            if (! in_array('spec:'.$name, $skip, true)) {
                $query->whereHas('specs', fn (Builder $spec) => $spec->where('name', $name)->whereIn('value', $values));
            }
        }

        return $query;
    }

    /**
     * Every word of the search has to match the name, SKU, manufacturer code
     * or brand. Accents and case don't matter (the collation takes care).
     *
     * @param  Builder<Product>  $query
     */
    private function search(Builder $query, string $search): void
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach (array_slice($words, 0, 6) as $word) {
            // "2.5" is how many people type the "2,5" of "2,5 mm²".
            $variants = preg_match('/^\d+\.\d+$/', $word) === 1 ? [$word, str_replace('.', ',', $word)] : [$word];

            $query->where(function (Builder $any) use ($variants) {
                foreach ($variants as $variant) {
                    $like = '%'.$this->escapeLike($variant).'%';

                    $any->orWhere('products.name', 'like', $like)
                        ->orWhere('products.sku', 'like', $like)
                        ->orWhere('products.manufacturer_code', 'like', $like)
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('brands.name', 'like', $like));
                }
            });
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function sort(Builder $query, ProductFilters $filters): void
    {
        $price = $this->priceExpression($filters->user);

        match ($filters->effectiveSort()) {
            // An exact SKU or manufacturer code first, then names that start with the search.
            CatalogSort::Relevance => $query->orderByRaw(
                'CASE WHEN products.sku = ? THEN 0 WHEN products.manufacturer_code = ? THEN 1 WHEN products.name LIKE ? THEN 2 ELSE 3 END',
                [$filters->search, $filters->search, $this->escapeLike($filters->search).'%'],
            )->orderBy('products.name'),
            CatalogSort::Name => $query->orderBy('products.name'),
            CatalogSort::PriceAsc => $query->orderByRaw("{$price} asc")->orderBy('products.name'),
            CatalogSort::PriceDesc => $query->orderByRaw("{$price} desc")->orderBy('products.name'),
            CatalogSort::Newest => $query->orderByDesc('products.created_at'),
        };
    }

    /**
     * The price a visitor pays, as SQL: professional accounts use the
     * professional price when the product has one.
     */
    private function priceExpression(?User $user): string
    {
        return $user?->hasProfessionalPricing()
            ? 'COALESCE(products.professional_price, products.list_price)'
            : 'products.list_price';
    }

    private function escapeLike(string $text): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
    }
}
