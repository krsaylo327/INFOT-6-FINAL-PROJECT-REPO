<?php

namespace App\Notifications;

use App\Models\EndorsementLetter;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EndorsementSubmittedForReview extends Notification
{
    public function __construct(private EndorsementLetter $letter) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $letter = $this->letter;
        $dean   = $letter->dean->name ?? 'A Dean';
        $event  = $letter->invitation->event_name ?? 'an event';

        return (new MailMessage)
            ->subject("Endorsement Letter Submitted for Review")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$dean} has submitted an endorsement letter for your review.")
            ->line("**Event:** {$event}")
            ->line("**Category:** " . ucfirst($letter->category))
            ->line("**Endorsed Staff:** " . $letter->staff->count() . ' staff member(s)')
            ->line("**Estimated Cost:** ₱" . number_format($letter->estimated_cost, 2))
            ->action('Review Endorsement', route('endorsement-letters.show', $letter))
            ->line('Please review and take action at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'endorsement_review',
            'message' => "An endorsement letter from {$this->letter->dean->name} requires your review.",
            'url'     => route('endorsement-letters.show', $this->letter),
        ];
    }
}
