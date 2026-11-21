<?php

namespace App\Http\Middleware;

use App\Services\DonorSessionStore;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class StartDonorSession implements AuthenticatesRequests
{
    public const COOKIE = 'medixa-donor-auth';

    public function __construct(private DonorSessionStore $donorSession) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $this->donorSession->session;
        $session->flush();
        $session->setId($request->cookies->get(self::COOKIE));
        $session->start();
        $response = $next($request);
        $session->save();
        $response->headers->setCookie(new Cookie(
            self::COOKIE,
            $session->getId(),
            config('session.expire_on_close') ? 0 : now()->addMinutes((int) config('session.lifetime'))->getTimestamp(),
            '/blood-connect',
            config('session.domain'),
            (bool) (config('session.secure') ?? $request->isSecure()),
            true,
            false,
            config('session.same_site', 'lax'),
        ));
        [$chances, $outOf] = config('session.lottery', [2, 100]);
        if (random_int(1, $outOf) <= $chances) {
            $session->getHandler()->gc((int) config('session.lifetime') * 60);
        }

        return $response;
    }
}
