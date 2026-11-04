<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Received = 'received';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case ReadyForPickup = 'ready_for_pickup';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::Confirmed => 'Confirmado',
            self::Preparing => 'En preparación',
            self::Shipped => 'Enviado',
            self::ReadyForPickup => 'Listo para retirar',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }
}
