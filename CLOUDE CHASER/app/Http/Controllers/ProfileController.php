<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user()->load('department');

        return view('profile.show', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'contact_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update(['contact_number' => $validated['contact_number']]);

        return back()->with('success', 'Profile updated.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user = auth()->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');
        $user->update(['avatar' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }

    /**
     * Upload signature as a file (PNG/JPG of scanned wet signature).
     */
    public function uploadSignature(Request $request): RedirectResponse
    {
        $request->validate([
            'signature' => ['required', 'image', 'max:1024', 'mimes:png,jpg,jpeg'],
        ]);

        $user = auth()->user();

        if ($user->signature_path) {
            \Storage::disk('private')->delete($user->signature_path);
        }

        $path = $request->file('signature')->store("signatures/{$user->id}", 'private');
        $user->update([
            'signature_path'        => $path,
            'signature_uploaded_at' => now(),
        ]);

        return back()->with('success', 'Signature uploaded successfully. It will be applied to documents you sign going forward.');
    }

    /**
     * Save signature drawn on the canvas (data URL → PNG file).
     */
    public function drawSignature(Request $request): RedirectResponse
    {
        $request->validate([
            'signature_data' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $user = auth()->user();

        $base64 = substr($request->input('signature_data'), strlen('data:image/png;base64,'));
        $binary = base64_decode($base64);

        if (!$binary || strlen($binary) < 100) {
            return back()->withErrors(['signature_data' => 'Signature appears empty. Please sign before saving.']);
        }

        if ($user->signature_path) {
            \Storage::disk('private')->delete($user->signature_path);
        }

        $filename = "signatures/{$user->id}/" . uniqid('sig_', true) . '.png';
        \Storage::disk('private')->put($filename, $binary);

        $user->update([
            'signature_path'        => $filename,
            'signature_uploaded_at' => now(),
        ]);

        return back()->with('success', 'Signature saved. It will be applied to documents you sign going forward.');
    }

    /**
     * Stream the current user's signature image (private disk).
     */
    public function showSignature(\App\Models\User $user)
    {
        // Any authenticated user may view a signatory's signature as it appears on
        // official documents they can already access (Travel Orders, endorsement letters).
        if (!$user->signature_path) {
            abort(404);
        }

        return \Storage::disk('private')->response($user->signature_path);
    }

    /**
     * Delete the current user's signature.
     */
    public function deleteSignature(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->signature_path) {
            \Storage::disk('private')->delete($user->signature_path);
            $user->update([
                'signature_path'        => null,
                'signature_uploaded_at' => null,
            ]);
        }

        return back()->with('success', 'Signature removed.');
    }
}
