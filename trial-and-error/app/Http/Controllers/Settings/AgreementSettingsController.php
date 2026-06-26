<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgreementSettingsController extends Controller
{
    public function index()
    {
        $reminderDays = AppSetting::getValue('agreements.reminder_days', config('agreements.reminder_days'));
        $notifyRoles = AppSetting::getValue('agreements.notify_roles', config('agreements.notify_roles'));

        return Inertia::render('settings/agreements', [
            'reminderDays' => $reminderDays,
            'notifyRoles' => $notifyRoles,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'reminderDays' => ['required', 'array'],
            'reminderDays.*' => ['integer', 'min:1'],
            'notifyRoles' => ['required', 'array'],
            'notifyRoles.*' => ['string'],
        ]);

        AppSetting::setValue('agreements.reminder_days', array_values($data['reminderDays']));
        AppSetting::setValue('agreements.notify_roles', array_values($data['notifyRoles']));

        return redirect()->back();
    }
}
