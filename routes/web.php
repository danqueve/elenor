<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\PhoneController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Livewire\Catalog\ProductList;
use Illuminate\Support\Facades\Route;

/*
| URLs are in Spanish (customers see them); route names are in English.
| Sections that don't exist yet render the "coming soon" page so no link
| in the layout ends in a 404. Each one is replaced by its real page in
| the stage that builds it.
*/

Route::get('/', HomeController::class)->name('home');

/*
| The catalog. One Livewire component serves every listing (the whole
| catalog, a category, the offers and the search results).
*/

Route::get('/catalogo', ProductList::class)->name('catalog.index');
Route::get('/categoria/{category}', ProductList::class)->name('categories.show');
Route::get('/ofertas', ProductList::class)->defaults('collection', 'ofertas')->name('offers.index');
Route::get('/buscar', ProductList::class)->name('search');
Route::get('/producto/{product}', [ProductController::class, 'show'])->name('products.show');

Route::view('/presupuestos', 'coming-soon', ['title' => 'Presupuestos'])->name('quotes.index');
Route::view('/favoritos', 'coming-soon', ['title' => 'Favoritos'])->name('favorites.index');
Route::view('/carrito', 'coming-soon', ['title' => 'Carrito'])->name('cart.index');
Route::view('/pedidos', 'coming-soon', ['title' => 'Mis pedidos'])->name('orders.index');

/*
| Sign in with Google is the only way in. The auth routes are for visitors
| who are not signed in; the account routes for those who are.
*/

Route::middleware('guest')->group(function () {
    Route::view('/ingresar', 'auth.login')->name('login');

    Route::middleware('throttle:20,1')->prefix('auth/google')->name('auth.google.')->group(function () {
        Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
        Route::get('callback', [GoogleAuthController::class, 'callback'])->name('callback');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/cuenta', [AccountController::class, 'show'])->name('account');
    Route::get('/cuenta/telefono', [PhoneController::class, 'edit'])->name('account.phone.edit');
    Route::put('/cuenta/telefono', [PhoneController::class, 'update'])->name('account.phone.update');
    Route::post('/salir', LogoutController::class)->name('logout');
});
