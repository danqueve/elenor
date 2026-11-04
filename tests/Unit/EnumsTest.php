<?php

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Enums\IvaCondition;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Enums\ProfessionalStatus;
use App\Enums\ShippingMethod;
use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    /**
     * @return array<string, array{class-string<\BackedEnum>}>
     */
    public static function enums(): array
    {
        return [
            'AccountType' => [AccountType::class],
            'IvaCondition' => [IvaCondition::class],
            'OrderStatus' => [OrderStatus::class],
            'PaymentMethod' => [PaymentMethod::class],
            'PaymentStatus' => [PaymentStatus::class],
            'ProductUnit' => [ProductUnit::class],
            'ProfessionalStatus' => [ProfessionalStatus::class],
            'ShippingMethod' => [ShippingMethod::class],
            'UserRole' => [UserRole::class],
        ];
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     */
    #[DataProvider('enums')]
    public function test_every_case_has_its_own_spanish_label(string $enum): void
    {
        $labels = array_map(fn ($case) => $case->label(), $enum::cases());

        foreach ($labels as $label) {
            $this->assertNotSame('', trim($label));
        }

        $this->assertSame($labels, array_unique($labels), "{$enum} repeats a label.");
    }

    public function test_order_statuses_are_the_agreed_ones_with_spanish_labels(): void
    {
        $labels = [];

        foreach (OrderStatus::cases() as $status) {
            $labels[$status->value] = $status->label();
        }

        $this->assertSame([
            'received' => 'Recibido',
            'confirmed' => 'Confirmado',
            'preparing' => 'En preparación',
            'shipped' => 'Enviado',
            'ready_for_pickup' => 'Listo para retirar',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ], $labels);
    }

    public function test_the_stored_values_match_the_data_model(): void
    {
        $values = fn (string $enum) => array_column($enum::cases(), 'value');

        $this->assertSame(['final', 'professional', 'company'], $values(AccountType::class));
        $this->assertSame(['none', 'pending', 'approved', 'rejected'], $values(ProfessionalStatus::class));
        $this->assertSame(['customer', 'admin', 'sales', 'warehouse'], $values(UserRole::class));
        $this->assertSame(['u', 'm', 'rollo'], $values(ProductUnit::class));
    }
}
