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

    /**
     * What the customer is told under each option in the checkout.
     */
    public function description(): string
    {
        return match ($this) {
            self::BankTransfer => 'Te pasamos los datos de la cuenta al confirmar. Preparamos el pedido cuando se acredita.',
            self::CashOnPickup => 'Pagás en efectivo cuando retirás el pedido.',
            self::MercadoPago => 'Tarjeta de crédito, débito o dinero en cuenta, en cuotas.',
        };
    }

    /**
     * The methods the checkout offers for a shipping method. Mercado Pago is
     * not offered yet (it needs its webhook: the order is paid only when the
     * webhook says so, never because of the redirect). Cash only makes sense
     * when the customer picks the order up.
     *
     * @return list<self>
     */
    public static function offeredFor(ShippingMethod $shipping): array
    {
        return $shipping === ShippingMethod::Pickup
            ? [self::BankTransfer, self::CashOnPickup]
            : [self::BankTransfer];
    }
}
