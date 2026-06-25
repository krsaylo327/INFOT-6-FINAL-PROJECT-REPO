<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Notifications\NewUserPending;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }


        return view('auth.register', [
            // Only academic colleges may self-register — exclude admin offices
            'departments' => Department::whereNotIn('abbreviation', ['HR', 'FIN', 'PRES'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'            => ['required', Rules\Password::defaults(), 'confirmed'],
            'employee_id'         => ['required', 'string', 'max:50'],
            'department_id'       => ['required', 'exists:departments,id'],
            'requested_position'  => ['required', 'string', 'max:255'],
            'contact_number'      => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name'               => $validated['name'],
            'email'              => strtolower($validated['email']),
            'password'           => Hash::make($validated['password']),
'role'               => 'traveler',
            'status'             => User::STATUS_PENDING,
            'employee_id'        => $validated['employee_id'],
            'requested_position' => $validated['requested_position'],
            'contact_number'     => $validated['contact_number'] ?? null,
            'department_id'      => $validated['department_id'],
        ]);

        AuditLogger::log('user.registered', $user);

        event(new Registered($user));

        User::where('role', 'admin')->each(fn($admin) => $admin->notify(new NewUserPending($user)));

        return redirect()->route('login')
            ->with('success', 'Registration submitted! Your account is pending admin approval. You will be able to log in once approved.');
    }
}

