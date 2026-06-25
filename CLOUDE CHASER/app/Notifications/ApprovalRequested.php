<?php

namespace App\Notifications;

use App\Models\Approval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequested extends Notification
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
            ->subject("Approval Required: {$tr->request_no}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A travel request requires your **Level {$this->approval->level}** approval.")
            ->line("Request No: {$tr->request_no}")
            ->line("Traveler: {$tr->user->name}")
            ->line("Destination: {$tr->destination}")
            ->line("Dates: {$tr->date_from->format('M d, Y')} – {$tr->date_to->format('M d, Y')}")
            ->action('Review Request', route('approvals.index'))
            ->line('Please review and take action at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        $tr = $this->approval->travelRequest;

        return [
            'type'    => 'approval',
            'message' => "A Level {$this->approval->level} approval is waiting for your review — {$tr->request_no} ({$tr->destination}).",
            'url'     => route('approvals.index'),
        ];
    }
}
