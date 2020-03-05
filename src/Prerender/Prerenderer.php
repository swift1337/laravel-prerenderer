<?php

namespace Swift1337\Prerender\Prerender;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Facades\Agent;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Prerenderer
 * @package Swift1337\Prerender\Prerender
 */
class Prerenderer
{
    // these constants are used as options for Prerender crawlers
    public const PRERENDER_SIGN = '_prerender_enabled';
    public const PRERENDER_DEVICE_PARAMETER = '_prerender_device';

    /** @var Guzzle */
    protected $client;

    /** @var string|null */
    protected $prerenderToken;

    /**
     * List of crawler user agents that will be
     *
     * @var array
     */
    protected $crawlerUserAgents;

    /**
     * URI whitelist for prerendering pages only on this list
     *
     * @var array
     */
    protected $whitelist;

    /**
     * URI blacklist for prerendering pages that are not on the list
     *
     * @var array
     */
    protected $blacklist;

    /**
     * Base URI to make the prerender requests
     *
     * @var string
     */
    protected $prerenderUri;

    /**
     * Return soft 3xx and 404 HTTP codes
     *
     * @var string
     */
    protected $returnSoftHttpCodes;

    public function __construct(array $config)
    {
        $this->returnSoftHttpCodes = $config['prerender_soft_http_codes'];
        $this->prerenderUri = $config['prerender_url'];
        $this->crawlerUserAgents = $config['crawler_user_agents'];
        $this->prerenderToken = $config['prerender_token'];
        $this->whitelist = $config['whitelist'];
        $this->blacklist = $config['blacklist'];

        $this->client = new Guzzle([
            'allow_redirects' => $config['prerender_soft_http_codes'],
        ]);
    }

    public function shouldPrerender(Request $request): bool
    {
        $shouldPrerender = false;

        $userAgent = strtolower($request->server->get('HTTP_USER_AGENT'));
        $requestUri = $request->getRequestUri();

        if (empty($userAgent)
            || $request->isMethod('GET') === false
            || $request->get(static::PRERENDER_SIGN)) {
            return false;
        }

        // 1. Prerender if _escaped_fragment_ is in the query string
        if ($request->query->has('_escaped_fragment_')) {
            $shouldPrerender = true;
        }

        // 2. Prerender if a crawler is detected
        foreach ($this->crawlerUserAgents as $crawlerUserAgent) {
            if (Str::contains($userAgent, strtolower($crawlerUserAgent))) {
                $shouldPrerender = true;
                break;
            }
        }

        if ($shouldPrerender === false) {
            return false;
        }

        // 3. Filter by whitelist
        if ($this->whitelist) {
            if ($this->isListed($requestUri, $this->whitelist) === false) {
                return false;
            }
        }

        // 4. Reject by blacklist
        if ($this->blacklist) {
            if ($this->isListed($requestUri, $this->blacklist)) {
                return false;
            }
        }

        return true;
    }

    public function fetchPrerenderedPage(Request $request): ResponseInterface
    {
        $options = [
            'headers' => [
                'User-Agent' => $request->server->get('HTTP_USER_AGENT'),
            ],
        ];

        if ($this->prerenderToken) {
            $options['headers']['X-Prerender-Token'] = $this->prerenderToken;
        }

        return $this->client->get($this->prepareQueryUrl($request), $options);
    }

    public function returnSoftHttpCodes(): bool
    {
        return $this->returnSoftHttpCodes;
    }

    /**
     * This logic helps to determine if original request was from desktop crawler or from mobile
     * @param Request $request
     * @return string
     */
    protected function prepareQueryUrl(Request $request): string
    {
        $additionalQueryParameters = [
            static::PRERENDER_DEVICE_PARAMETER => Agent::isDesktop() || !Agent::isPhone() ? 'desktop' : 'mobile',
            static::PRERENDER_SIGN => 1,
        ];

        return $this->prerenderUri . '/' . urlencode($request->fullUrlWithQuery($additionalQueryParameters));
    }

    /**
     * Check whether one or more needles are in the given list
     *
     * @param string|string[] $paths
     * @param array $list
     * @return bool
     */
    protected function isListed($paths, array $list): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];

        foreach ($list as $pattern) {
            foreach ($paths as $path) {
                if (Str::is($pattern, $path)) {
                    return true;
                }
            }
        }

        return false;
    }
}
