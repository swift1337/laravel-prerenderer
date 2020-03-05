<?php

namespace Swift1337\Prerender\Middleware;

use Closure;
use Illuminate\Http\Request;
use Swift1337\Prerender\Prerender\Prerenderer;

/**
 * Class PrerenderPage
 * @package Swift1337\Prerender\Middleware
 */
class PrerenderPage
{
    /** @var Prerenderer */
    protected $prerenderer;

    public function __construct(Prerenderer $prerenderer)
    {
        $this->prerenderer = $prerenderer;
    }

    public function handle(Request $request, Closure $next)
    {
        return $this->prerenderer->shouldPrerender($request)
            ? $this->prerender($request)
            : $next($request);
    }

    protected function prerender(Request $request)
    {
        $response = $this->prerenderer->fetchPrerenderedPage($request);
        $statusCode = $response->getStatusCode();

        if ($this->prerenderer->returnSoftHttpCodes() && $statusCode >= 300 && $statusCode < 400) {
            return redirect($response->getHeader('Location'), $statusCode);
        }

        return response($response->getBody());
    }
}
