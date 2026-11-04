<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\IvaCondition;
use App\Enums\ProfessionalStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\Cuit;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds. Safe to run more than once: users are matched
     * by email.
     *
     * The admin is the only account created in production. Nobody has a
     * Google id yet: on the first Google login the account is matched by
     * email and linked.
     */
    public function run(): void
    {
        $this->user(config('shop.admin_email'), 'Administración Elenor', UserRole::Admin);

        if (app()->isProduction()) {
            return;
        }

        $this->user('ventas@elenor.test', 'Lucía Ventas', UserRole::Sales);
        $this->user('deposito@elenor.test', 'Marcos Depósito', UserRole::Warehouse);

        $marcela = $this->user('marcela.gomez@example.com', 'Marcela Gómez');
        $marcela->addresses()->firstOrCreate(
            ['street' => 'Avenida Aconquija', 'number' => '1250'],
            ['city' => 'Yerba Buena', 'province' => 'Tucumán', 'postal_code' => '4107', 'reference' => 'Casa con portón negro'],
        );

        $javier = $this->user('javier.ledesma@example.com', 'Javier Ledesma', accountType: AccountType::Professional, status: ProfessionalStatus::Approved);
        $javier->addresses()->firstOrCreate(
            ['street' => 'Calle Lamadrid', 'number' => '480'],
            ['city' => 'San Miguel de Tucumán', 'province' => 'Tucumán', 'postal_code' => '4000', 'reference' => null],
        );
        $javier->fiscalData()->updateOrCreate([], [
            'business_name' => 'Javier Ledesma',
            'cuit' => $this->cuit('2030123456'),
            'iva_condition' => IvaCondition::Monotax,
        ]);

        $company = $this->user('compras@constructoranoa.example.com', 'Constructora del NOA S.R.L.', accountType: AccountType::Company, status: ProfessionalStatus::Pending);
        $company->addresses()->firstOrCreate(
            ['street' => 'Avenida Sarmiento', 'number' => '2100'],
            ['city' => 'San Miguel de Tucumán', 'province' => 'Tucumán', 'postal_code' => '4000', 'reference' => 'Oficina en el primer piso'],
        );
        $company->fiscalData()->updateOrCreate([], [
            'business_name' => 'Constructora del NOA S.R.L.',
            'cuit' => $this->cuit('3071234567'),
            'iva_condition' => IvaCondition::RegisteredResponsible,
        ]);
    }

    /**
     * Role, account type and professional status are privileged fields: they
     * are set with forceFill on purpose, never through mass assignment.
     */
    private function user(
        string $email,
        string $name,
        UserRole $role = UserRole::Customer,
        AccountType $accountType = AccountType::FinalConsumer,
        ProfessionalStatus $status = ProfessionalStatus::None,
    ): User {
        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'role' => $role,
            'account_type' => $accountType,
            'professional_status' => $status,
        ])->save();

        return $user;
    }

    /**
     * Complete the first ten digits with their check digit.
     */
    private function cuit(string $firstTenDigits): string
    {
        return $firstTenDigits.Cuit::checkDigit($firstTenDigits);
    }
}
