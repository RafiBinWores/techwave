<?php

namespace App\Http\Controllers;

use App\Models\TrackedQrCode;
use App\Support\QrCode\ScanTracker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QrScanController extends Controller
{
    public function __invoke(Request $request, string $token, ScanTracker $tracker): Response
    {
        $code = TrackedQrCode::where('token', $token)->firstOrFail();
        if ($request->isMethod('HEAD') || str_contains(strtolower($request->header('Sec-Purpose', $request->header('Purpose', ''))), 'prefetch')) {
            return response('', 204)->header('Cache-Control', 'private, no-store');
        }
        if (! $tracker->record($code)) {
            $reset = now()->timezone(config('qr.scan_timezone'))->addDay()->startOfDay();

            return response()->view('qr.scan', ['limited' => true, 'reset' => $reset], 429)
                ->header('Cache-Control', 'private, no-store')
                ->header('Retry-After', max(1, $reset->getTimestamp() - now()->getTimestamp()));
        }

        $content = $code->content;
        if ($code->content_type === 'url') {
            abort_unless(filter_var($content, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $content), 404);

            return redirect()->away($content)->header('Cache-Control', 'private, no-store');
        }
        abort_unless(in_array($code->content_type, ['text', 'wifi', 'email', 'phone'], true), 404);
        $action = match ($code->content_type) {
            'email' => str_starts_with($content, 'mailto:') ? $content : null,
            'phone' => str_starts_with($content, 'tel:') ? $content : null,
            default => null,
        };

        return response()->view('qr.scan', [
            'limited' => false, 'type' => $code->content_type, 'content' => $content, 'action' => $action,
            'wifi' => $code->content_type === 'wifi' ? $this->wifiDetails($content) : [],
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function wifiDetails(string $content): array
    {
        $fields = [];
        $field = '';
        $escaped = false;
        foreach (str_split(substr($content, 5)) as $character) {
            if ($escaped) {
                $field .= $character;
                $escaped = false;
            } elseif ($character === '\\') {
                $escaped = true;
            } elseif ($character === ';') {
                [$key, $value] = array_pad(explode(':', $field, 2), 2, '');
                $fields[$key] = $value;
                $field = '';
            } else {
                $field .= $character;
            }
        }

        return $fields;
    }
}
