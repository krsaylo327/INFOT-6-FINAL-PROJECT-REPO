<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementSubscription;
use Illuminate\Http\Request;

class AgreementSubscriptionController extends Controller
{
    public function store(Request $request, $id)
    {
        $agreement = Agreement::findOrFail($id);

        $this->authorize('view', $agreement);

        $sub = AgreementSubscription::updateOrCreate(
            ['agreement_id' => $agreement->id, 'user_id' => auth()->id()],
            ['notify_on_expiration' => true]
        );

        return response()->json(['ok' => true, 'subscribed' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $agreement = Agreement::findOrFail($id);

        $this->authorize('view', $agreement);

        AgreementSubscription::where('agreement_id', $agreement->id)->where('user_id', auth()->id())->delete();

        return response()->json(['ok' => true, 'subscribed' => false]);
    }
}
