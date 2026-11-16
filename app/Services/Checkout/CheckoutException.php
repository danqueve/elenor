<?php

namespace App\Services\Checkout;

use RuntimeException;

/**
 * The order can't be placed (empty cart, not enough stock...). The message is
 * in Spanish and is shown to the customer.
 */
class CheckoutException extends RuntimeException {}
