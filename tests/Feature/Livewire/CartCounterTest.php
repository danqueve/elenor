<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CartCounter;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class CartCounterTest extends TestCase
{
    public function test_it_links_to_the_cart_and_starts_empty(): void
    {
        Livewire::test(CartCounter::class)
            ->assertSee('Carrito')
            ->assertSee('sin productos')
            ->assertSeeHtml('href="'.route('cart.index').'"')
            ->assertSet('count', 0);
    }

    public function test_the_count_cannot_be_set_from_the_browser(): void
    {
        $this->expectException(PublicPropertyNotFoundException::class);

        Livewire::test(CartCounter::class)->set('count', 99);
    }
}
