<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        // Hidden products don't exist for the public.
        abort_unless($product->is_active, 404);

        $product->load(['category', 'brand', 'images', 'specs']);

        $path = $product->category->ancestorsAndSelf();

        return view('products.show', [
            'product' => $product,
            'path' => $path,
            'related' => $this->related($product, $path->first()),
        ]);
    }

    /**
     * Up to four products to look at next: the same subcategory (preferring
     * the same brand) and, if there are not enough, the rest of the main category.
     *
     * @return Collection<int, Product>
     */
    private function related(Product $product, Category $root): Collection
    {
        $others = fn () => Product::query()->active()->whereKeyNot($product->id)->with('brand')->withFirstImage();

        $related = $others()
            ->where('products.category_id', $product->category_id)
            ->orderByRaw('products.brand_id <=> ? DESC', [$product->brand_id])
            ->orderBy('products.name')
            ->limit(4)
            ->get();

        if ($related->count() < 4) {
            $related = $related->concat(
                $others()
                    ->whereIn('products.category_id', $root->descendantIds())
                    ->whereNotIn('products.id', $related->modelKeys())
                    ->orderBy('products.name')
                    ->limit(4 - $related->count())
                    ->get(),
            );
        }

        return $related;
    }
}
