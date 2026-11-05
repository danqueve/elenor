<?php

namespace App\Livewire\Catalog;

use App\Enums\CatalogSort;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CatalogNavigation;
use App\Services\Catalog\ProductCatalog;
use App\Services\Catalog\ProductFilters;
use App\Support\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The product listing: the whole catalog, a category, the offers or a search,
 * with filters, order and pages that change without reloading. The state is
 * kept in the URL, so any listing can be shared.
 *
 * Everything that comes from the browser (the public properties) is cleaned
 * before it reaches a query: see ProductFilters::clean*().
 */
class ProductList extends Component
{
    use WithPagination;

    private const PER_PAGE = 12;

    /** The category page being shown (set from the route, the browser can't change it). */
    #[Locked]
    public ?Category $category = null;

    /** "ofertas" on the offers page. */
    #[Locked]
    public ?string $collection = null;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    /** @var list<string> Selected brand ids. */
    #[Url(as: 'marca', except: [])]
    public array $brands = [];

    /** @var array<string, list<string>> Attribute name => selected values. */
    #[Url(as: 'attr', except: [])]
    public array $specs = [];

    #[Url(as: 'min', except: '')]
    public string $min = '';

    #[Url(as: 'max', except: '')]
    public string $max = '';

    /** A CatalogSort value, or empty for the default order. */
    #[Url(as: 'orden', except: '')]
    public string $sort = '';

    public function mount(?Category $category = null, ?string $collection = null): void
    {
        $this->category = $category;
        $this->collection = $collection === 'ofertas' ? 'ofertas' : null;
    }

    /**
     * Any change of the filters goes back to the first page, and the lists
     * are tidied (no empty groups, brands as strings so the checkboxes match).
     */
    public function updated(string $name): void
    {
        if (str_starts_with($name, 'paginators')) {
            return;
        }

        $this->brands = array_map('strval', ProductFilters::cleanBrandIds($this->brands));
        $this->specs = ProductFilters::cleanSpecs($this->specs);

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('brands', 'specs', 'min', 'max');
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->q = '';
        $this->resetPage();
    }

    public function removeBrand(int $id): void
    {
        $this->brands = array_values(array_filter($this->brands, fn ($brand) => (int) $brand !== $id));
        $this->resetPage();
    }

    /**
     * A checkbox of the attribute filters. These are not bound with wire:model:
     * Livewire only treats a group of checkboxes as a list when the property
     * is already an array, and a group nobody has used yet is not there.
     */
    public function toggleSpec(string $name, string $value): void
    {
        $this->changeSpec($name, $value, ! in_array($value, $this->specs[$name] ?? [], true));
    }

    public function removeSpec(string $name, string $value): void
    {
        $this->changeSpec($name, $value, false);
    }

    public function clearPrice(): void
    {
        $this->reset('min', 'max');
        $this->resetPage();
    }

    public function render(ProductCatalog $catalog, CatalogNavigation $navigation): View
    {
        $filters = $this->filters();

        $brandFacet = $catalog->brandFacet($filters);
        $default = $filters->search !== '' ? CatalogSort::Relevance : CatalogSort::Name;
        $heading = $this->heading();

        return view('livewire.catalog.product-list', [
            'filters' => $filters,
            'products' => $this->products($catalog, $filters),
            'brandFacet' => $brandFacet,
            'specFacets' => $catalog->specFacets($filters),
            'priceBounds' => $catalog->priceBounds($filters),
            'navigation' => $navigation->for($this->category),
            'breadcrumb' => $this->breadcrumb($heading),
            'heading' => $heading,
            'chips' => $this->chips($filters, $brandFacet),
            'defaultSort' => $default,
            'sortOptions' => collect(CatalogSort::cases())
                ->reject(fn (CatalogSort $sort) => $sort === $default || ($sort === CatalogSort::Relevance && $filters->search === ''))
                ->mapWithKeys(fn (CatalogSort $sort) => [$sort->value => $sort->label()]),
            'user' => auth()->user(),
            'whatsapp' => WhatsApp::url($filters->search !== ''
                ? "¡Hola! Estoy buscando: {$filters->search}"
                : '¡Hola! Quería hacer una consulta sobre un producto.'),
        ])
            ->title($heading)
            ->layoutData(['description' => $this->description($heading)]);
    }

    private function changeSpec(string $name, string $value, bool $selected): void
    {
        $others = array_diff($this->specs[$name] ?? [], [$value]);

        $this->specs[$name] = $selected ? [...$others, $value] : array_values($others);
        $this->specs = ProductFilters::cleanSpecs($this->specs);
        $this->resetPage();
    }

    /**
     * What the browser sent, cleaned.
     */
    private function filters(): ProductFilters
    {
        $min = ProductFilters::cleanPrice($this->min);
        $max = ProductFilters::cleanPrice($this->max);

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return new ProductFilters(
            category: $this->category,
            search: mb_substr(trim($this->q), 0, 100),
            brandIds: ProductFilters::cleanBrandIds($this->brands),
            specs: ProductFilters::cleanSpecs($this->specs),
            minPrice: $min,
            maxPrice: $max,
            sort: CatalogSort::tryFrom($this->sort),
            onlyOffers: $this->collection === 'ofertas',
            user: auth()->user(),
        );
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    private function products(ProductCatalog $catalog, ProductFilters $filters): LengthAwarePaginator
    {
        $page = fn () => $catalog->products($filters)->with('brand')->withFirstImage()->paginate(self::PER_PAGE);

        $products = $page();

        // A page beyond the last one (an old link, or fewer results than before): show the last page.
        if ($products->isEmpty() && $products->currentPage() > 1) {
            $this->setPage($products->lastPage());
            $products = $page();
        }

        return $products;
    }

    private function heading(): string
    {
        return match (true) {
            trim($this->q) !== '' => 'Resultados para «'.mb_substr(trim($this->q), 0, 100).'»',
            $this->category !== null => $this->category->name,
            $this->collection === 'ofertas' => 'Ofertas',
            default => 'Catálogo',
        };
    }

    private function description(string $heading): string
    {
        return match (true) {
            $this->category !== null => "{$heading}: comprá online materiales eléctricos y herramientas en Tucumán y el NOA.",
            $this->collection === 'ofertas' => 'Ofertas en materiales eléctricos y herramientas.',
            default => 'Catálogo de materiales eléctricos y herramientas para Tucumán y el NOA.',
        };
    }

    /**
     * @return list<array{label: string, url: ?string}>
     */
    private function breadcrumb(string $heading): array
    {
        $items = [['label' => 'Inicio', 'url' => route('home')]];

        if ($this->category !== null) {
            $items[] = ['label' => 'Catálogo', 'url' => route('catalog.index')];

            foreach ($this->category->ancestorsAndSelf() as $category) {
                $items[] = ['label' => $category->name, 'url' => route('categories.show', $category)];
            }

            return $items;
        }

        if (trim($this->q) !== '') {
            $items[] = ['label' => 'Catálogo', 'url' => route('catalog.index')];
            $heading = 'Búsqueda';
        }

        $items[] = ['label' => $heading, 'url' => null];

        return $items;
    }

    /**
     * The active filters as removable chips. "action" is the Livewire call
     * that removes it.
     *
     * @param  Collection<int, object{id: int, name: string, total: int}>  $brandFacet
     * @return list<array{label: string, action: string}>
     */
    private function chips(ProductFilters $filters, Collection $brandFacet): array
    {
        $chips = [];
        $brandNames = $brandFacet->pluck('name', 'id');

        foreach ($filters->brandIds as $id) {
            $chips[] = ['label' => 'Marca: '.($brandNames[$id] ?? $id), 'action' => "removeBrand({$id})"];
        }

        foreach ($filters->specs as $name => $values) {
            foreach ($values as $value) {
                $chips[] = [
                    'label' => "{$name}: {$value}",
                    'action' => 'removeSpec('.json_encode($name, JSON_UNESCAPED_UNICODE).', '.json_encode($value, JSON_UNESCAPED_UNICODE).')',
                ];
            }
        }

        if ($filters->minPrice !== null || $filters->maxPrice !== null) {
            $chips[] = [
                'label' => 'Precio: '.match (true) {
                    $filters->minPrice !== null && $filters->maxPrice !== null => money($filters->minPrice, 0).' a '.money($filters->maxPrice, 0),
                    $filters->minPrice !== null => 'desde '.money($filters->minPrice, 0),
                    default => 'hasta '.money($filters->maxPrice, 0),
                },
                'action' => 'clearPrice()',
            ];
        }

        return $chips;
    }
}
