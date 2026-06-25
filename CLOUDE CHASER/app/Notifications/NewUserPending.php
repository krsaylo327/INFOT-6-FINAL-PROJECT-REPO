<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserPending extends Notification
{
    public function __construct(private User $newUser) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New User Registration Pending Approval")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new user has registered and is awaiting your approval.")
            ->line("Name: {$this->newUser->name}")
            ->line("Email: {$this->newUser->email}")
            ->action('Review Registration', route('admin.users.index'))
            ->line('Please approve or reject this registration.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'user',
            'message' => "New registration pending approval: {$this->newUser->name} ({$this->newUser->email}).",
            'url'     => route('admin.users.index'),
        ];
    }
}
