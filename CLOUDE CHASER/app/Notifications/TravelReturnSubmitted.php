<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelReturnSubmitted extends Notification
{
    public function __construct(private TravelOrder $travelOrder, private string $travelerName) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $to = $this->travelOrder;

        return (new MailMessage)
            ->subject("Travel Completed — {$to->to_number}: {$to->event_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->travelerName}** has returned from travel and submitted a return attestation.")
            ->line("**Travel Order:** {$to->to_number}")
            ->line("**Event:** {$to->event_name}")
            ->line("**Dates:** {$to->formattedDates()}")
            ->line('The Records Office will verify the attestation and formally close the document.')
            ->action('View Travel Order', route('travel-orders.show', $to));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'travel_returned',
            'message' => "{$this->travelerName} has returned from {$this->travelOrder->event_name} ({$this->travelOrder->to_number}).",
            'url'     => route('travel-orders.show', $this->travelOrder),
        ];
    }
}
