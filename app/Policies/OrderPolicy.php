<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Customers see only their own orders. (Staff get their own screens in
     * the admin panel, stage 6.)
     */
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function reorder(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}
