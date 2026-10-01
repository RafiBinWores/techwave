<?php

namespace App\Http\Controllers;

use App\Services\TempMailService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class TempMailAttachmentController extends Controller
{
    /**
     * The provider token outlives the HTTP request that created the mailbox so
     * that images inside the sandboxed message body (which cannot send the
     * session cookie) can still be served.
     */
    public const TOKEN_CACHE_PREFIX = 'temp-mail.token.';

    /**
     * Stream an attachment from the mailbox provider. The URL is signed and
     * carries the mailbox id, so possession of the link is the only grant.
     */
    public function __invoke(
        Request $request,
        string $mailbox,
        string $message,
        string $attachment,
        TempMailService $service,
    ): Response {
        $token = $this->token($mailbox);

        abort_unless($token !== null, 404);

        $payload = $service->attachment($token, $message, $attachment);

        abort_unless($payload !== null, 404);

        $contentType = $this->contentType($payload['content_type']);
        $filename = $this->filename((string) $request->query('name', ''));
        $forceDownload = $request->boolean('download');
        $inline = ! $forceDownload
            && (str_starts_with($contentType, 'image/') || $contentType === 'application/pdf');

        return response($payload['content'], 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => ($inline ? 'inline' : 'attachment')
                .'; filename="'.$this->asciiFilename($filename).'"'
                ."; filename*=UTF-8''".rawurlencode($filename),
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
        ]);
    }

    private function token(string $mailbox): ?string
    {
        if (! preg_match('/^[A-Za-z0-9_-]{6,64}$/', $mailbox)) {
            return null;
        }

        $cached = Cache::get(self::TOKEN_CACHE_PREFIX.$mailbox);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $sessionMailbox = session('temp_mail');

        if (
            is_array($sessionMailbox)
            && ($sessionMailbox['id'] ?? '') === $mailbox
            && is_string($sessionMailbox['token'] ?? '')
        ) {
            return $sessionMailbox['token'];
        }

        return null;
    }

    private function contentType(string $contentType): string
    {
        $contentType = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $contentType) ?? '');

        if ($contentType === '' || ! str_contains($contentType, '/')) {
            return 'application/octet-stream';
        }

        return $contentType;
    }

    private function filename(string $filename): string
    {
        $filename = str_replace(['/', '\\'], '_', trim($filename));
        $filename = trim(preg_replace('/[\x00-\x1F\x7F"]/', '', $filename) ?? '');

        return $filename !== '' ? $filename : 'attachment';
    }

    private function asciiFilename(string $filename): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?? 'attachment';

        return $ascii !== '' ? $ascii : 'attachment';
    }
}
