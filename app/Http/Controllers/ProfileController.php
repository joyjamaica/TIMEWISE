<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $photo = $request->file('profile_photo');
        unset($validated['profile_photo']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $oldPhotoPath = $user->profile_photo_path;
        if ($photo) {
            $photoPath = $photo->store('profile-photos', 'public');
            if (!$photoPath) {
                throw new RuntimeException('The profile photo could not be saved. Please try again.');
            }
            $user->profile_photo_path = $photoPath;
        }

        $user->save();

        if ($photo && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();
        $profilePhotoPath = $user->profile_photo_path;

        Auth::logout();

        $user->delete();
        if ($profilePhotoPath) {
            Storage::disk('public')->delete($profilePhotoPath);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
