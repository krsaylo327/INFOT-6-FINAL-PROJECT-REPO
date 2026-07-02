<?php

namespace App\Mail;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgreementExpiring extends Mailable
{
    use Queueable, SerializesModels;

    public Agreement $agreement;

    public int $days;

    public function __construct(Agreement $agreement, int $days)
    {
        $this->agreement = $agreement;
        $this->days = $days;
    }

    public function build()
    {
        return $this->subject("Agreement expiring soon: {$this->agreement->title}")
            ->markdown('emails.agreement_expiring')
            ->with([
                'agreement' => $this->agreement,
                'days' => $this->days,
            ]);
    }
}
