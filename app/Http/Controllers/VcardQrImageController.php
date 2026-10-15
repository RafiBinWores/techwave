<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Vcard;
use App\Services\UploadStorage;
use App\Support\QrCode\ScanTracker;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class VcardQrImageController extends Controller
{
    public function __invoke(string $vcard): Response
    {
        $card = Vcard::query()->where('user_id', auth()->id())->findOrFail($vcard);
        $svg = (string) QrCode::format('svg')->size(1024)->margin(1)->errorCorrection('H')
            ->generate(route('vcard.public.show', ['slug' => $card->slug]));
        $premium = app(ScanTracker::class)->premium(auth()->user());
        $path = $premium
            ? (($card->qr_logo_mode === 'custom' && $card->qr_has_logo) ? $card->qr_logo_path : null)
            : SiteSetting::current()->logo;
        $logo = UploadStorage::dataUri($path);
        if ($path && (! $logo || ! str_starts_with($logo, 'data:image/'))) {
            return response()->json(['message' => 'The saved QR logo could not be loaded. Please upload it again in the vCard editor.'], 422);
        }
        if ($logo) {
            $logo = htmlspecialchars($logo, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $overlay = '<rect x="397" y="397" width="230" height="230" rx="34" fill="white"/><image x="421" y="421" width="182" height="182" preserveAspectRatio="xMidYMid meet" href="'.$logo.'"/>';
            $svg = str_replace('</svg>', $overlay.'</svg>', $svg);
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src data:; sandbox",
        ]);
    }
}
