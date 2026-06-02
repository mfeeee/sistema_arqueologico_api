<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'perfil'         => $user->perfil,
            'classificacao'  => $user->classificacao,
            'avatar_url'     => $user->avatar_url,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'perfil'         => $user->perfil,
            'classificacao'  => $user->classificacao,
            'avatar_url'     => $user->avatar_url,
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_url) {
            $oldPath = str_replace(Storage::disk('s3')->url(''), '', $user->avatar_url);
            Storage::disk('s3')->delete($oldPath);
        }

        $path = Storage::disk('s3')->putFile('avatars', $request->file('avatar'), 'public');
        $url  = Storage::disk('s3')->url($path);

        $user->update(['avatar_url' => $url]);

        return response()->json(['avatar_url' => $url]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_url) {
            $path = str_replace(Storage::disk('s3')->url(''), '', $user->avatar_url);
            Storage::disk('s3')->delete($path);
        }

        $user->update(['avatar_url' => null]);

        return response()->json(['avatar_url' => null]);
    }
}
