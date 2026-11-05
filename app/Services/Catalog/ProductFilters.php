<?php

namespace App\Services\Catalog;

use App\Enums\CatalogSort;
use App\Models\Category;
use App\Models\User;

/**
 * Everything a catalog listing can be narrowed or ordered by. The static
 * clean*() methods turn untrusted input (the URL, Livewire properties) into
 * safe values.
 */
final readonly class ProductFilters
{
    /**
     * @param  list<int>  $brandIds
     * @param  array<string, list<string>>  $specs  Attribute name => the selected values.
     */
    public function __construct(
        public ?Category $category = null,
        public string $search = '',
        public array $brandIds = [],
        public array $specs = [],
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public ?CatalogSort $sort = null,
        public bool $onlyOffers = false,
        public ?User $user = null,
    ) {}

    /**
     * The order that applies: relevance only makes sense when searching, and
     * it is the default then; otherwise the list is alphabetical.
     */
    public function effectiveSort(): CatalogSort
    {
        $default = $this->search !== '' ? CatalogSort::Relevance : CatalogSort::Name;

        if ($this->sort === null || ($this->sort === CatalogSort::Relevance && $this->search === '')) {
            return $default;
        }

        return $this->sort;
    }

    /**
     * How many filters are active, counting each selected value (and the
     * price range as one).
     */
    public function activeFilterCount(): int
    {
        return count($this->brandIds)
            + array_sum(array_map('count', $this->specs))
            + ($this->minPrice !== null || $this->maxPrice !== null ? 1 : 0);
    }

    /**
     * @return list<int>
     */
    public static function cleanBrandIds(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        return collect($input)
            ->filter(fn ($id) => (is_int($id) || is_string($id)) && ctype_digit((string) $id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(50)
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function cleanSpecs(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $clean = [];

        foreach (array_slice($input, 0, 12, true) as $name => $values) {
            if (! is_string($name) || $name === '' || mb_strlen($name) > 60 || ! is_array($values)) {
                continue;
            }

            $values = collect($values)
                ->filter(fn ($value) => is_string($value) && $value !== '' && mb_strlen($value) <= 120)
                ->unique()
                ->take(30)
                ->values()
                ->all();

            if ($values !== []) {
                $clean[$name] = $values;
            }
        }

        return $clean;
    }

    /**
     * A whole-peso price typed by a person: "1.500" => 1500, "1500,50" => 1500.
     */
    public static function cleanPrice(mixed $input): ?int
    {
        if (! is_string($input) && ! is_int($input)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', explode(',', (string) $input)[0]);

        return $digits === '' ? null : min((int) $digits, 99_999_999);
    }
}
