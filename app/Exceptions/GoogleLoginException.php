<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A Google login that has to be refused. The message is written for the
 * customer and is shown on the login page.
 */
class GoogleLoginException extends RuntimeException
{
    public static function missingData(): self
    {
        return new self('Google no nos compartió los datos necesarios (tu nombre y tu email). Probá de nuevo.');
    }

    public static function unverifiedEmail(): self
    {
        return new self('El email de tu cuenta de Google todavía no está verificado. Verificalo en Google y probá de nuevo.');
    }

    public static function accountConflict(): self
    {
        return new self('Ese email ya está asociado a otra cuenta de Google. Escribinos y lo resolvemos.');
    }
}
