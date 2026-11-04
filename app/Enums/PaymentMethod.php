<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case CashOnPickup = 'cash_on_pickup';
    case MercadoPago = 'mercado_pago';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Transferencia bancaria',
            self::CashOnPickup => 'Efectivo al retirar',
            self::MercadoPago => 'Mercado Pago',
        };
    }
}
