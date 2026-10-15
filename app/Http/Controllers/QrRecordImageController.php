<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\TrackedQrCode;
use App\Support\QrCode\DesignGenerator;
use App\Support\QrCode\LogoOptimizer;
use App\Support\QrCode\ScanTracker;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class QrRecordImageController extends Controller
{
    public function __invoke(string $qrCode): Response
    {
        $code = TrackedQrCode::query()->where('user_id', auth()->id())->findOrFail($qrCode);
        $svg = $code->svg;
        if (! $svg) {
            $svg = (string) (new DesignGenerator)->format('svg')->size(900)->margin(4)
                ->errorCorrection('H')->generate(route('qr.scan', ['token' => $code->token]));
            $logo = null;
            $path = SiteSetting::current()->logo;
            if ($path && Storage::disk('public')->exists($path)) {
                $contents = Storage::disk('public')->get($path);
                try {
                    $logo = app(LogoOptimizer::class)->normalize($contents, (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'image/png');
                } catch (InvalidArgumentException) {
                    // An invalid optional logo must not prevent premium exports.
                }
            }
            if (! $logo && ! app(ScanTracker::class)->premium(auth()->user())) {
                return response()->json(['message' => 'The site logo is unavailable. Please contact the site administrator.'], 422);
            }
            if ($logo) {
                $image = '<rect x="342" y="342" width="216" height="216" rx="18" fill="white"/><image x="355.5" y="355.5" width="189" height="189" preserveAspectRatio="xMidYMid meet" href="data:'.$logo['mime'].';base64,'.base64_encode($logo['contents']).'"/>';
                $svg = str_replace('</svg>', $image.'</svg>', $svg);
            }
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src data:; sandbox",
        ]);
    }
}
