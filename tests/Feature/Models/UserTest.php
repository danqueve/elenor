<?php

namespace Tests\Feature\Models;

use App\Enums\AccountType;
use App\Enums\ProfessionalStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{AccountType, ProfessionalStatus, bool}>
     */
    public static function professionalPricing(): array
    {
        return [
            'final consumer' => [AccountType::FinalConsumer, ProfessionalStatus::None, false],
            'professional approved' => [AccountType::Professional, ProfessionalStatus::Approved, true],
            'professional pending' => [AccountType::Professional, ProfessionalStatus::Pending, false],
            'professional rejected' => [AccountType::Professional, ProfessionalStatus::Rejected, false],
            'professional without request' => [AccountType::Professional, ProfessionalStatus::None, false],
            'company approved' => [AccountType::Company, ProfessionalStatus::Approved, true],
            'company pending' => [AccountType::Company, ProfessionalStatus::Pending, false],
            'final consumer with a stale approval' => [AccountType::FinalConsumer, ProfessionalStatus::Approved, false],
        ];
    }

    #[DataProvider('professionalPricing')]
    public function test_only_approved_professional_and_company_accounts_get_professional_pricing(
        AccountType $type,
        ProfessionalStatus $status,
        bool $expected,
    ): void {
        $user = new User;
        $user->account_type = $type;
        $user->professional_status = $status;

        $this->assertSame($expected, $user->hasProfessionalPricing());
    }

    public function test_a_new_user_is_a_final_customer_by_default(): void
    {
        $user = User::create([
            'name' => 'Marcela Gómez',
            'email' => 'marcela@example.com',
            'google_id' => '118234567890123456789',
            'avatar' => 'https://lh3.googleusercontent.com/a/example',
            'phone' => '+54 381 555-1234',
        ])->refresh();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame(AccountType::FinalConsumer, $user->account_type);
        $this->assertSame(ProfessionalStatus::None, $user->professional_status);
    }

    public function test_privileged_fields_cannot_be_mass_assigned(): void
    {
        $this->expectException(MassAssignmentException::class);

        User::create([
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function names(): array
    {
        return [
            'two words' => ['Marcela Gómez', 'Marcela', 'MG'],
            'several words' => ['  María de los Ángeles   Pérez ', 'María', 'MP'],
            'one word' => ['Lucía', 'Lucía', 'L'],
            'accents and lowercase' => ['álvaro ñandú', 'álvaro', 'ÁÑ'],
        ];
    }

    #[DataProvider('names')]
    public function test_it_gives_the_first_name_and_the_initials(string $name, string $first, string $initials): void
    {
        $user = new User(['name' => $name]);

        $this->assertSame($first, $user->firstName());
        $this->assertSame($initials, $user->initials());
    }

    public function test_the_factory_states_set_role_and_account_type(): void
    {
        $this->assertSame(UserRole::Admin, User::factory()->admin()->make()->role);
        $this->assertSame(UserRole::Sales, User::factory()->sales()->make()->role);
        $this->assertSame(UserRole::Warehouse, User::factory()->warehouse()->make()->role);
        $this->assertTrue(User::factory()->professional()->make()->hasProfessionalPricing());
        $this->assertTrue(User::factory()->company()->make()->hasProfessionalPricing());
        $this->assertFalse(User::factory()->pendingProfessional()->make()->hasProfessionalPricing());
        $this->assertNull(User::factory()->unverified()->make()->email_verified_at);
    }
}
