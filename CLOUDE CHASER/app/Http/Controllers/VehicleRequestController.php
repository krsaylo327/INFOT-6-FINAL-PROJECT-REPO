<?php

namespace App\Http\Controllers;

use App\Models\TravelOrder;
use App\Models\VehicleRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class VehicleRequestController extends Controller
{
    public function store(Request $request, TravelOrder $travelOrder)
    {
        $user = auth()->user();

        // Only the traveler or dean on the TO may request a vehicle
        if ($travelOrder->traveler_id !== $user->id && $travelOrder->dean_id !== $user->id && $user->role !== 'admin') {
            abort(403);
        }

        // Vehicle requests can only be made after the President has issued the TO
        if (!$travelOrder->isIssued()) {
            return back()->with('error', 'A vehicle request can only be submitted after the Travel Order has been issued by the President.');
        }

        if ($travelOrder->vehicleRequest()->exists()) {
            return back()->with('error', 'A vehicle request already exists for this Travel Order.');
        }

        $validated = $request->validate([
            'vehicle_type'        => ['required', 'string', 'max:100'],
            'departure_datetime'  => ['required', 'date'],
            'return_datetime'     => ['required', 'date', 'after_or_equal:departure_datetime'],
            'passengers'          => ['required', 'integer', 'min:1', 'max:99'],
            'pickup_location'     => ['required', 'string', 'max:255'],
            'dropoff_location'    => ['required', 'string', 'max:255'],
            'purpose'             => ['required', 'string', 'min:10'],
        ]);

        VehicleRequest::create([
            'travel_order_id'    => $travelOrder->id,
            'requested_by'       => $user->id,
            'vehicle_type'       => $validated['vehicle_type'],
            'departure_datetime' => $validated['departure_datetime'],
            'return_datetime'    => $validated['return_datetime'],
            'passengers'         => $validated['passengers'],
            'pickup_location'    => $validated['pickup_location'],
            'dropoff_location'   => $validated['dropoff_location'],
            'purpose'            => $validated['purpose'],
            'status'             => 'pending',
        ]);

        AuditLogger::log('vehicle_request.submitted', $travelOrder, ['requested_by' => $user->id]);

        return back()->with('success', 'Vehicle request submitted. Admin will review and confirm.');
    }

    public function updateStatus(Request $request, VehicleRequest $vehicleRequest)
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'status'        => ['required', 'in:approved,denied'],
            'admin_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $vehicleRequest->update([
            'status'        => $validated['status'],
            'admin_remarks' => $validated['admin_remarks'] ?? null,
            'reviewed_by'   => $user->id,
            'reviewed_at'   => now(),
        ]);

        AuditLogger::log('vehicle_request.' . $validated['status'], $vehicleRequest->travelOrder, [
            'reviewed_by' => $user->id,
            'status'      => $validated['status'],
        ]);

        return back()->with('success', 'Vehicle request ' . $validated['status'] . '.');
    }
}
