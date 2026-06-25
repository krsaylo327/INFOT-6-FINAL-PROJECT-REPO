<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    private function gate(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
    }

    public function index(): View
    {
        $this->gate();

        // HR and Finance have no role in the travel workflow — exclude their users.
        $users = User::with('department')
            ->where('id', '!=', auth()->id())
            ->whereDoesntHave('department', fn ($q) => $q->whereIn('abbreviation', ['HR', 'FIN']))
            ->latest()
            ->get();

        $stats = [
            'total'     => $users->count(),
            'active'    => $users->where('status', User::STATUS_ACTIVE)->count(),
            'pending'   => $users->where('status', User::STATUS_PENDING)->count(),
            'disabled'  => $users->where('status', User::STATUS_DISABLED)->count(),
            'rejected'  => $users->where('status', User::STATUS_REJECTED)->count(),
        ];

        $departments = Department::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'stats', 'departments'));
    }

    public function show(User $user): View
    {
        $this->gate();

        $user->load('department');

        // Confirmation token — only needed for pending users
        $token = null;
        if ($user->isPending()) {
            $key = "user_token_{$user->id}";
            if (!session()->has($key)) {
                session([$key => strtoupper(Str::random(6))]);
            }
            $token = session($key);
        }

        $departments = Department::orderBy('name')->get();

        // Activity counts
        $travelRequestCount = $user->travelRequests()->count();
        $approvalCount      = $user->approvalsToReview()->whereIn('action', ['approved', 'rejected'])->count();

        return view('admin.users.show', compact('user', 'token', 'departments', 'travelRequestCount', 'approvalCount'));
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $this->gate();

        if (!$user->isPending()) {
            return back()->with('error', 'User is not pending approval.');
        }

        $request->validate(['token' => ['required', 'string']]);

        $expected = session("user_token_{$user->id}");
        if (!$expected || strtoupper($request->token) !== $expected) {
            return back()->withErrors(['token' => 'Incorrect confirmation code.'])->withInput();
        }

        session()->forget("user_token_{$user->id}");

        $user->update([
            'status'      => User::STATUS_ACTIVE,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        AuditLogger::log('user.approved', $user, ['approved_by' => auth()->id()]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "{$user->name}'s account has been approved.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $this->gate();

        if (!$user->isPending()) {
            return back()->with('error', 'User is not pending approval.');
        }

        $request->validate([
            'token'            => ['required', 'string'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $expected = session("user_token_{$user->id}");
        if (!$expected || strtoupper($request->token) !== $expected) {
            return back()->withErrors(['token' => 'Incorrect confirmation code.'])->withInput();
        }

        session()->forget("user_token_{$user->id}");

        $user->update([
            'status'           => User::STATUS_REJECTED,
            'rejection_reason' => $request->rejection_reason ?? null,
        ]);

        AuditLogger::log('user.rejected', $user, ['rejected_by' => auth()->id()]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "{$user->name}'s account has been rejected.");
    }

    public function disable(Request $request, User $user): RedirectResponse
    {
        $this->gate();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot disable your own account.');
        }

        if (!$user->isActive()) {
            return back()->with('error', 'Only active accounts can be disabled.');
        }

        $request->validate([
            'disable_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        // Invalidate the user's active sessions (SESSION_DRIVER=database)
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->update([
            'status'         => User::STATUS_DISABLED,
            'disabled_at'    => now(),
            'disable_reason' => $request->disable_reason,
            'disabled_by'    => auth()->id(),
        ]);

        AuditLogger::log('user.disabled', $user, [
            'disabled_by' => auth()->id(),
            'reason'      => $request->disable_reason,
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "{$user->name}'s account has been disabled.");
    }

    public function enable(User $user): RedirectResponse
    {
        $this->gate();

        if (!$user->isDisabled()) {
            return back()->with('error', 'This account is not disabled.');
        }

        $user->update([
            'status'         => User::STATUS_ACTIVE,
            'disabled_at'    => null,
            'disable_reason' => null,
            'disabled_by'    => null,
        ]);

        AuditLogger::log('user.enabled', $user, ['enabled_by' => auth()->id()]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "{$user->name}'s account has been enabled.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->gate();

        if (!$user->isRejected()) {
            return back()->with('error', 'Only rejected accounts can be reactivated.');
        }

        $user->update([
            'status'           => User::STATUS_ACTIVE,
            'rejection_reason' => null,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        AuditLogger::log('user.reactivated', $user, ['reactivated_by' => auth()->id()]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "{$user->name}'s account has been reactivated.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->gate();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Use your profile page to edit your own account.');
        }

        $validated = $request->validate([
            'role'               => ['required', 'in:admin,approver,dean,traveler,records_officer'],
            'department_id'      => ['nullable', 'exists:departments,id'],
            'requested_position' => ['nullable', 'string', 'max:255'],
            'approver_level'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'approver_type'      => ['nullable', 'in:vp_academic,research_director,vp_research'],
        ]);

        $user->update([
            'role'               => $validated['role'],
            'department_id'      => $validated['department_id'] ?? null,
            'requested_position' => $validated['requested_position'] ?? null,
            'approver_level'     => $validated['role'] === 'approver' ? ($validated['approver_level'] ?? null) : null,
            'approver_type'      => $validated['role'] === 'approver' ? ($validated['approver_type'] ?? null) : null,
        ]);

        AuditLogger::log('user.updated', $user, [
            'updated_by' => auth()->id(),
            'role'       => $validated['role'],
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "{$user->name}'s account has been updated.");
    }
}
