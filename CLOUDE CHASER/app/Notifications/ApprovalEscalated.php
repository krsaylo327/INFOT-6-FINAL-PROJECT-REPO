<?php

namespace App\Notifications;

use App\Models\Approval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalEscalated extends Notification
{
    public function __construct(private Approval $approval) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr = $this->approval->travelRequest;

        return (new MailMessage)
            ->subject("Escalation Alert: Stale Approval — {$tr->request_no}")
            ->greeting("Hello {$notifiable->name},")
            ->line("An approval has been pending for too long and requires your immediate attention.")
            ->line("Request No: {$tr->request_no}")
            ->line("Destination: {$tr->destination}")
            ->line("Stuck at: Level {$this->approval->level}")
            ->action('Review Now', route('approvals.index'))
            ->line('This is an automated escalation notice from the UA Travel Management system.');
    }

    public function toArray(object $notifiable): array
    {
        $tr = $this->approval->travelRequest;

        return [
            'type'    => 'escalation',
            'message' => "Approval for {$tr->request_no} ({$tr->destination}) has been pending at Level {$this->approval->level} for too long and requires attention.",
            'url'     => route('approvals.index'),
        ];
    }
}
