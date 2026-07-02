<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgreementVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UploaderReconciliationController extends Controller
{
    public function index()
    {
        // show versions where uploaded_by_id is null but uploaded_by (name) exists
        $versions = AgreementVersion::whereNull('uploaded_by_id')
            ->whereNotNull('uploaded_by')
            ->orderBy('created_at', 'desc')
            ->get();

        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('UploaderReconciliation', [
            'versions' => $versions,
            'users' => $users,
        ]);
    }

    public function map(Request $request)
    {
        $data = $request->validate([
            'version_id' => 'required|integer|exists:agreement_versions,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $version = AgreementVersion::findOrFail($data['version_id']);
        $user = User::findOrFail($data['user_id']);

        $version->uploaded_by_id = $user->id;
        // keep legacy uploaded_by name for compatibility, but update to matched name
        $version->uploaded_by = $user->name;
        $version->save();

        return redirect()->back();
    }
}
