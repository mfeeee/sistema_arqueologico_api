<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FotoUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fotos' => 'required|array|max:10',
            'fotos.*' => 'image|mimes:png,jpeg,jpg,webp,heic,heif|max:5120',
        ]);

        $urls = [];
        foreach ($request->file('fotos') as $foto) {
            $path = Storage::disk('s3')->putFile('midias', $foto, 'public');
            $urls[] = Storage::disk('s3')->url($path);
        }

        return response()->json(['urls' => $urls], 201);
    }
}
