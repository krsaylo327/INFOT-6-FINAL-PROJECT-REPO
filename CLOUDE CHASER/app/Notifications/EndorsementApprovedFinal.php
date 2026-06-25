<?php

namespace App\Notifications;

use App\Models\EndorsementLetter;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EndorsementApprovedFinal extends Notification
{
    public function __construct(private EndorsementLetter $letter) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $letter = $this->letter;
        $event  = $letter->invitation->event_name ?? 'an event';
        $toNumber = $letter->travelOrder?->to_number;

        $message = (new MailMessage)
            ->subject("Endorsement Approved — Travel Order Generated: {$event}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Good news! The endorsement for your participation in **{$event}** has been **APPROVED** by " . ($letter->reviewer->name ?? $letter->reviewerLabel()) . ".")
            ->line("**Destination:** " . ($letter->invitation->destination ?? 'TBD'))
            ->line("**Dates:** " . ($letter->invitation->formattedDates() ?? 'TBD'));

        if ($toNumber) {
            $message->line("**Your Travel Order has been auto-generated:** {$toNumber}")
                    ->line("It is now awaiting the President's signature. You will be notified once it is released by the Records Office.")
                    ->action('View Travel Order', route('travel-orders.show', $letter->travelOrder));
        } else {
            $message->line('Your Travel Order will be auto-generated shortly. You will be notified once it is released.')
                    ->action('View Endorsement', route('endorsement-letters.show', $letter));
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        $to = $this->letter->travelOrder;

        return [
            'type'    => 'endorsement_approved',
            'message' => $to
                ? "Endorsement approved for {$this->letter->invitation->event_name}. Travel Order {$to->to_number} generated."
                : "Endorsement approved for {$this->letter->invitation->event_name}. Travel Order will be generated shortly.",
            'url'     => $to
                ? route('travel-orders.show', $to)
                : route('endorsement-letters.show', $this->letter),
        ];
    }
}
