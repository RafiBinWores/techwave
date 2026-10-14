<?php

namespace App\Http\Controllers;

use App\Support\QrCode\PreviewStore;
use Symfony\Component\HttpFoundation\Response;

class QrPreviewController extends Controller
{
    public function __invoke(string $token, string $version, PreviewStore $previews): Response
    {
        $svg = $previews->get($token, $version);
        abort_if($svg === null, 404);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src data:; sandbox",
        ]);
    }
}
