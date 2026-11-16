<?php

namespace App\Services\Cart;

use RuntimeException;

/**
 * Something the customer asked of the cart can't be done. The message is in
 * Spanish and is shown to them as is.
 */
class CartException extends RuntimeException {}
