<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DevLoginController extends Controller
{
    public function index()
    {
        $users = User::with('department')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $selectedUserId = session('dev_user_id');

        return view('dev_login.index', compact('users', 'selectedUserId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        session(['dev_user_id' => $validated['user_id']]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Development user switched successfully.');
    }

    public function destroy()
    {
        session()->forget('dev_user_id');

        return redirect()
            ->route('dev-login.index')
            ->with('success', 'Development session ended.');
    }
}