<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\View\View;

class AdminDepartmentController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $departments = Department::withCount([
            'users',
            'users as active_count'   => fn ($q) => $q->where('status', 'active'),
            'users as pending_count'  => fn ($q) => $q->where('status', 'pending'),
            'users as approver_count' => fn ($q) => $q->where('role', 'approver'),
            'users as traveler_count' => fn ($q) => $q->where('role', 'traveler'),
        ])->orderBy('name')->get();

        $unassignedCount = User::whereNull('department_id')->count();

        return view('admin.departments.index', compact('departments', 'unassignedCount'));
    }

    public function show(Department $department): View
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $users = $department->users()->latest()->get();

        $stats = [
            'total'    => $users->count(),
            'active'   => $users->where('status', 'active')->count(),
            'pending'  => $users->where('status', 'pending')->count(),
            'approver' => $users->where('role', 'approver')->count(),
            'traveler' => $users->where('role', 'traveler')->count(),
        ];

        return view('admin.departments.show', compact('department', 'users', 'stats'));
    }
}
