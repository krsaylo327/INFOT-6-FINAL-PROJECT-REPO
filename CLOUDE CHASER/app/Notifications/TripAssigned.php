<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripAssigned extends Notification
{
    public function __construct(private TravelRequest $travelRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr = $this->travelRequest;

        return (new MailMessage)
            ->subject("Trip Assignment: {$tr->destination}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been assigned a trip to **{$tr->destination}**.")
            ->line("Request No: {$tr->request_no}")
            ->line("Dates: {$tr->date_from->format('M d, Y')} – {$tr->date_to->format('M d, Y')}")
            ->action('View Travel Request', route('travel-requests.show', $tr))
            ->line('Please acknowledge or decline this assignment at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'trip',
            'message' => "You have been assigned a trip to {$this->travelRequest->destination}.",
            'url'     => route('travel-requests.show', $this->travelRequest),
        ];
    }
}
