<?php

namespace App\Http\Middleware;

use App\Models\Visit;
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
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldTrack($request)) {
            try {
                Visit::create([
                    'session_id' => $request->session()->getId(),
                    'user_id' => $request->user()?->id,
                    'url' => $request->path() === '/' ? '/' : '/'.$request->path(),
                    'referer' => $request->header('referer'),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                ]);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $next($request);
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
