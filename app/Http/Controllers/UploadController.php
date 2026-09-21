<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $path): StreamedResponse
    {
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $inline = in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/avif', 'image/x-icon', 'image/vnd.microsoft.icon'], true);

        return $disk->response($path, null, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }
}
