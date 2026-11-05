<?php

namespace Tests\Unit;

use App\Support\ImageUrl;
use App\Support\Money;
use App\Support\WhatsApp;
use Tests\TestCase;

class SupportHelpersTest extends TestCase
{
    public function test_money_can_be_shown_in_whole_pesos(): void
    {
        $peso = '$'."\u{00A0}";

        $this->assertSame($peso.'12.346', Money::format(12345.67, 0));
        $this->assertSame($peso.'5.400', money(5400, 0));
        $this->assertSame($peso.'5.400,00', money(5400));
    }

    public function test_whatsapp_builds_the_link_from_the_configured_number(): void
    {
        config(['shop.contact.whatsapp' => '+54 9 381 555-1234']);

        $this->assertSame('https://wa.me/5493815551234', WhatsApp::url());
    }

    public function test_whatsapp_encodes_the_message(): void
    {
        config(['shop.contact.whatsapp' => '5493815551234']);

        $url = WhatsApp::url("¡Hola! Cable 2,5 mm² (SKU CAB-1)\nhttps://elenor.test/producto/x?a=1&b=2");

        $this->assertStringStartsWith('https://wa.me/5493815551234?text=', $url);
        $this->assertStringNotContainsString(' ', $url);
        $this->assertStringNotContainsString("\n", $url);
        $this->assertSame(
            "¡Hola! Cable 2,5 mm² (SKU CAB-1)\nhttps://elenor.test/producto/x?a=1&b=2",
            rawurldecode(substr($url, strlen('https://wa.me/5493815551234?text='))),
        );
    }

    public function test_whatsapp_is_null_without_a_number(): void
    {
        config(['shop.contact.whatsapp' => null]);
        $this->assertNull(WhatsApp::url('hola'));

        config(['shop.contact.whatsapp' => '  ']);
        $this->assertNull(WhatsApp::url());
    }

    public function test_image_urls_come_from_the_public_disk_unless_they_are_already_urls(): void
    {
        $this->assertNull(ImageUrl::for(null));
        $this->assertNull(ImageUrl::for(''));
        $this->assertSame('/storage/products/cable.jpg', ImageUrl::for('products/cable.jpg'));
        $this->assertSame('https://cdn.example.com/cable.jpg', ImageUrl::for('https://cdn.example.com/cable.jpg'));
        $this->assertSame('//cdn.example.com/cable.jpg', ImageUrl::for('//cdn.example.com/cable.jpg'));
        $this->assertSame('/favicon.svg', ImageUrl::for('/favicon.svg'));
    }
}
