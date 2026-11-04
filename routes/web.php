<?php

use Illuminate\Support\Facades\Route;

/*
| URLs are in Spanish (customers see them); route names are in English.
| Sections that don't exist yet render the "coming soon" page so no link
| in the layout ends in a 404. Each one is replaced by its real page in
| the stage that builds it.
*/

Route::view('/', 'home')->name('home');

Route::view('/catalogo', 'coming-soon', ['title' => 'Catálogo'])->name('catalog.index');
Route::view('/buscar', 'coming-soon', ['title' => 'Búsqueda'])->name('search');
Route::view('/presupuestos', 'coming-soon', ['title' => 'Presupuestos'])->name('quotes.index');
Route::view('/favoritos', 'coming-soon', ['title' => 'Favoritos'])->name('favorites.index');
Route::view('/carrito', 'coming-soon', ['title' => 'Carrito'])->name('cart.index');
Route::view('/pedidos', 'coming-soon', ['title' => 'Mis pedidos'])->name('orders.index');
Route::view('/cuenta', 'coming-soon', ['title' => 'Mi cuenta'])->name('account');
Route::view('/ingresar', 'coming-soon', ['title' => 'Ingresar'])->name('login');
