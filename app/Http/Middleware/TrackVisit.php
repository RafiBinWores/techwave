<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use App\Services\UserAgentParser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function __construct(private readonly UserAgentParser $userAgentParser) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldTrack($request)) {
            try {
                $userAgent = substr((string) $request->userAgent(), 0, 255);

                $secChUa = $request->header('Sec-CH-UA');

                Visit::create([
                    'session_id' => $request->session()->getId(),
                    'user_id' => $request->user()?->id,
                    'url' => $request->path() === '/' ? '/' : '/'.$request->path(),
                    'referer' => $request->header('referer'),
                    'user_agent' => $userAgent,
                    'ip_address' => $request->ip(),
                    'device' => $this->userAgentParser->device($userAgent),
                    'browser' => $this->userAgentParser->browser($userAgent, $secChUa),
                    'os' => $this->userAgentParser->operatingSystem($userAgent),
                    'created_at' => now(),
                ]);
            } catch (Throwable $e) {
                report($e);
            }
        }

        $response = $next($request);

        // Advertise the client hint needed to detect the real browser brand
        // (Brave, etc.) from the User-Agent.
        $response->headers->set('Accept-CH', 'Sec-CH-UA');

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! $request->routeIs('admin.*')
            && ! $request->is('livewire/*')
            && ! $request->is('storage/*')
            && ! $request->is('build/*')
            && ! $request->is('up')
            && ! $request->is('vcard/*/photo');
    }
}
