<?php

namespace App\View\Components\Layout;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class MobileNav extends Component
{
    /**
     * The five fixed destinations of the bottom menu.
     *
     * @return list<array{label: string, url: string, icon: string, active: bool}>
     */
    public function items(): array
    {
        return [
            $this->item('Inicio', 'home', 'home', 'home'),
            $this->item('Catálogo', 'catalog.index', 'squares-2x2', ['catalog.*', 'search']),
            $this->item('Presupuestos', 'quotes.index', 'document-text', 'quotes.*'),
            $this->item('Pedidos', 'orders.index', 'shopping-bag', 'orders.*'),
            $this->item('Cuenta', Auth::check() ? 'account' : 'login', 'user', ['account', 'account.*', 'login']),
        ];
    }

    public function render(): View
    {
        return view('components.layout.mobile-nav', ['items' => $this->items()]);
    }

    /**
     * @param  string|list<string>  $active  Route name patterns that mark the item as active.
     * @return array{label: string, url: string, icon: string, active: bool}
     */
    private function item(string $label, string $route, string $icon, string|array $active): array
    {
        return [
            'label' => $label,
            'url' => route($route),
            'icon' => $icon,
            'active' => request()->routeIs(...(array) $active),
        ];
    }
}
