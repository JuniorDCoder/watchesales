<?php

namespace App\Http\Middleware;

use App\Models\Watch;
use App\Support\Analytics\VisitTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackStorefrontVisit
{
    public function __construct(private VisitTracker $tracker) {}

    /**
     * Count successful storefront page views once the response has been sent.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isSuccessful() && $this->tracker->shouldTrack($request)) {
            $watch = $request->route('watch');

            defer(fn () => $this->tracker->recordPageView($request, $watch instanceof Watch ? $watch : null));
        }

        return $response;
    }
}
