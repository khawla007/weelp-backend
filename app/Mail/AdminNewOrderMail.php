<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $order;
    public $itemName;
    public $addons;
    public $baseAmount;
    public $addonsAmount;
    public $currencySymbol;

    public function __construct($order)
    {
        $this->order = $order;

        $snapshot = is_array($order->item_snapshot_json)
            ? $order->item_snapshot_json
            : json_decode($order->item_snapshot_json, true);

        $this->itemName = $snapshot['name'] ?? 'N/A';
        $this->addons = $snapshot['addons'] ?? [];
        $this->baseAmount = $snapshot['base_amount'] ?? $order->payment->amount;
        $this->addonsAmount = $snapshot['addons_amount'] ?? 0;
        $this->currencySymbol = strtoupper($order->payment->currency ?? 'USD') === 'USD' ? '$' : strtoupper($order->payment->currency ?? '');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Admin New Order Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.admin.new',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
