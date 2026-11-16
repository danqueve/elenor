<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Recibimos tu pedido": sent to the customer after placing an order, with
 * the items, the totals and, for a transfer, the bank details.
 */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Recibimos tu pedido {$this->order->number}");
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items', 'user']);

        return new Content(markdown: 'mail.orders.placed', with: [
            'order' => $this->order,
            'bank' => array_filter(config('shop.bank', [])),
            'url' => route('orders.show', $this->order),
        ]);
    }
}
