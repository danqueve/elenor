<?php

namespace App\Services\Cart;

/**
 * Why a cart line can't be bought as it is.
 */
enum CartProblem: string
{
    case Unavailable = 'unavailable';
    case OutOfStock = 'out_of_stock';
    case NotEnoughStock = 'not_enough_stock';
    case InvalidQuantity = 'invalid_quantity';

    public function label(): string
    {
        return match ($this) {
            self::Unavailable => 'Este producto ya no está disponible. Quitalo para seguir.',
            self::OutOfStock => 'Se quedó sin stock. Quitalo para seguir.',
            self::NotEnoughStock => 'No hay stock para esa cantidad. Bajala para seguir.',
            self::InvalidQuantity => 'Revisá la cantidad.',
        };
    }
}
