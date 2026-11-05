<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Catalog\CatalogNavigation;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The home page: the main categories, the best offers and the newest products.
     */
    public function __invoke(CatalogNavigation $navigation): View
    {
        $products = fn () => Product::query()->active()->with('brand')->withFirstImage();

        return view('home', [
            'categories' => $navigation->roots(),
            // The biggest discounts first.
            'offers' => $products()
                ->onSale()
                ->orderByRaw('(products.compare_at_price - products.list_price) / products.compare_at_price DESC')
                ->orderBy('products.id')
                ->limit(8)
                ->get(),
            'latest' => $products()
                ->orderByDesc('products.created_at')
                ->orderByDesc('products.id')
                ->limit(8)
                ->get(),
        ]);
    }
}
