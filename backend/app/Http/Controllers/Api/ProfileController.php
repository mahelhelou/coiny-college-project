<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    /**
     * PRF-01: update the display name.
     */
    public function update(ProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return UserResource::make($user);
    }

    /**
     * PRF-02: change the password after confirming the current one.
     */
    public function updatePassword(PasswordRequest $request): Response
    {
        $request->user()->update(['password' => $request->validated('password')]);
        $request->session()->regenerate();

        return response()->noContent();
    }
}
