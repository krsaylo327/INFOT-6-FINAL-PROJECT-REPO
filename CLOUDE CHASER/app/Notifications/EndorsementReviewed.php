<?php

namespace App\Notifications;

use App\Models\EndorsementLetter;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EndorsementReviewed extends Notification
{
    public function __construct(private EndorsementLetter $letter) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $letter   = $this->letter;
        $event    = $letter->invitation->event_name ?? 'an event';
        $decision = strtoupper($letter->status);
        $reviewer = $letter->reviewer->name ?? $letter->reviewerLabel();

        $mail = (new MailMessage)
            ->subject("Endorsement Letter {$decision}: {$event}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your endorsement letter has been reviewed by {$reviewer}.")
            ->line("**Event:** {$event}")
            ->line("**Decision:** {$decision}");

        if ($letter->review_remarks) {
            $mail->line("**Remarks:** {$letter->review_remarks}");
        }

        if ($letter->isApproved()) {
            $mail->line('The President\'s Office will now issue the Travel Order for the endorsed staff.');
        } elseif ($letter->isRejected()) {
            $mail->line('You may revise the endorsement and resubmit for review.');
        }

        return $mail->action('View Endorsement', route('endorsement-letters.show', $letter));
    }

    public function toArray(object $notifiable): array
    {
        $decision = $this->letter->status;
        return [
            'type'    => 'endorsement_reviewed',
            'message' => "Your endorsement letter for {$this->letter->invitation->event_name} has been {$decision}.",
            'url'     => route('endorsement-letters.show', $this->letter),
        ];
    }
}
