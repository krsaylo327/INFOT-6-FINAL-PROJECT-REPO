<?php

namespace App\Notifications;

use App\Models\EndorsementLetter;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EndorsementStaffAssigned extends Notification
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

        return (new MailMessage)
            ->subject("You have been endorsed: {$event}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your dean has endorsed you to attend the following event:")
            ->line("**Event:** {$event}")
            ->line("**Destination:** " . ($letter->invitation->destination ?? 'TBD'))
            ->line("**Dates:** " . ($letter->invitation->formattedDates() ?? 'TBD'))
            ->line("The endorsement is currently being reviewed by the " . $letter->reviewerLabel() . ".")
            ->action('View Endorsement', route('endorsement-letters.show', $letter))
            ->line("You will be notified once the endorsement is officially approved.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'endorsement_assigned',
            'message' => "You have been endorsed for {$this->letter->invitation->event_name} — pending review.",
            'url'     => route('endorsement-letters.show', $this->letter),
        ];
    }
}
