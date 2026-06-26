<?php

namespace App\Observers;

use App\Models\Agreement;

class AgreementObserver
{
    /**
     * Handle the Agreement "saving" event.
     */
    public function saving(Agreement $agreement): void
    {
        // Ensure status reflects current workflow/dates before persisting
        $agreement->syncStatus();
    }
}
