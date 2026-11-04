<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AppConfigurationTest extends TestCase
{
    public function test_the_app_uses_tucuman_time(): void
    {
        $this->assertSame('America/Argentina/Tucuman', config('app.timezone'));
        $this->assertSame('America/Argentina/Tucuman', now()->timezoneName);
        $this->assertSame('-03:00', now()->format('P'));
    }

    public function test_the_app_speaks_spanish(): void
    {
        $this->assertSame('es', app()->getLocale());
        $this->assertSame('es', config('app.fallback_locale'));
        $this->assertSame('es_AR', config('app.faker_locale'));
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $errors = Validator::make([], ['email' => 'required'])->errors();

        $this->assertSame('El campo correo electrónico es obligatorio.', $errors->first('email'));
    }

    public function test_dates_are_formatted_in_spanish(): void
    {
        $date = CarbonImmutable::parse('2026-11-04', 'America/Argentina/Tucuman');

        $this->assertSame('miércoles 4 de noviembre', $date->translatedFormat('l j \d\e F'));
    }

    public function test_the_currency_is_argentine_pesos(): void
    {
        $this->assertSame('ARS', config('shop.currency.code'));
        $this->assertSame('$', config('shop.currency.symbol'));
    }
}
