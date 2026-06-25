<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestDecided extends Notification
{
    public function __construct(
        private TravelRequest $travelRequest,
        private string $action
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tr     = $this->travelRequest;
        $isApproved = $this->action === 'approved';
        $label  = $isApproved ? 'Approved' : 'Rejected';
        $color  = $isApproved ? 'success' : 'error';

        return (new MailMessage)
            ->subject("Travel Request {$label}: {$tr->request_no}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your travel request has been **{$label}**.")
            ->line("Request No: {$tr->request_no}")
            ->line("Destination: {$tr->destination}")
            ->line("Dates: {$tr->date_from->format('M d, Y')} – {$tr->date_to->format('M d, Y')}")
            ->action('View Request', route('travel-requests.show', $tr));
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->action === 'approved' ? 'approved ✓' : 'rejected ✗';

        return [
            'type'    => 'decision',
            'action'  => $this->action,
            'message' => "Your travel request {$this->travelRequest->request_no} to {$this->travelRequest->destination} was {$label}.",
            'url'     => route('travel-requests.show', $this->travelRequest),
        ];
    }
}
