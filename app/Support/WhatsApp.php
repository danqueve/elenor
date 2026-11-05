<?php

namespace App\Support;

class WhatsApp
{
    /**
     * wa.me link to the shop's WhatsApp, with an optional message already
     * written. Null when the shop has no WhatsApp number configured.
     */
    public static function url(?string $message = null): ?string
    {
        $number = preg_replace('/\D+/', '', (string) config('shop.contact.whatsapp'));

        if ($number === '') {
            return null;
        }

        $url = 'https://wa.me/'.$number;

        return $message === null ? $url : $url.'?text='.rawurlencode($message);
    }
}
