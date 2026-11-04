<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Admin = 'admin';
    case Sales = 'sales';
    case Warehouse = 'warehouse';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Cliente',
            self::Admin => 'Administrador',
            self::Sales => 'Ventas',
            self::Warehouse => 'Depósito',
        };
    }
}
