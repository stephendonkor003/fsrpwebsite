<?php

namespace App\Http\Middleware;

use App\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchVisibility
{
    public function __construct(private readonly Seo $seo) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $robots = $this->seo->robots($request);

        if ($response->getStatusCode() >= 400) {
            $robots = 'noindex, nofollow';
        }

        if (str_contains($robots, 'noindex')) {
            $response->headers->set('X-Robots-Tag', $robots);
        }

        return $response;
    }
}
