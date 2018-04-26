<?php

namespace Lullabot\Mpx\Service\AccessManagement;

use GuzzleHttp\Psr7\Uri;
use Lullabot\Mpx\AuthenticatedClient;
use Psr\Http\Message\ResponseInterface;

/**
 * Resolve all URLs for a given service.
 *
 * In general, ResolveDomain should be used instead, as it can return all
 * services at once. However, it requires an Account context, so if you do not
 * have one available use this instead.
 *
 * @see \Lullabot\Mpx\Service\AccessManagement\ResolveDomain
 * @see https://docs.theplatform.com/help/wsf-resolveallurls-method
 */
class ResolveAllUrls
{
    /**
     * While this method is not a data service, it still has a schema version.
     */
    const SCHEMA_VERSION = '1.0';

    /**
     * The URL of the method. Note that we hardcode the whole path as this is
     * where other services are bootstrapped from.
     */
    const RESOLVE_ALL_URLS_URL = 'https://access.auth.theplatform.com/web/Registry/resolveAllUrls';

    /**
     * An array of resolvedUrls URLs for the service.
     *
     * @var string[]
     */
    protected $resolvedUrls;

    /**
     * The service these URLs correspond to, such as 'Media Data Service'.
     *
     * @var string
     */
    protected $service;

    /**
     * ResolveAllUrls constructor.
     *
     * @param string $service  The service that was queried, such as 'Access Data Service'.
     * @param array  $data     The MPX response.
     * @param bool   $insecure (optional) Set to true to request the insecure version of this service.
     */
    public function __construct(string $service, array $data, bool $insecure = false)
    {
        if (!isset($data['resolveAllUrlsResponse'])) {
            throw new \InvalidArgumentException('Data does not contain a resolveAllUrlsResponse key and does not appear to be an MPX response.');
        }

        $this->resolvedUrls = $data['resolveAllUrlsResponse'];

        array_walk($this->resolvedUrls, function (&$value) use ($insecure) {
            $value = new Uri($value);
            if (!$insecure) {
                $value = $value->withScheme('https');
            }
        });

        $this->service = $service;
    }

    /**
     * Fetch URLs and return the response.
     *
     * @param \Lullabot\Mpx\AuthenticatedClient $authenticatedClient The authenticated session to use when querying.
     * @param string                            $service             The service to find URLs for, such as 'Media Data Service'.
     * @param bool                              $insecure            (optional) Set to true to request the insecure version of this service.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface A promise to return a new ResolveAllUrls class.
     */
    public static function load(AuthenticatedClient $authenticatedClient, string $service, bool $insecure = false)
    {
        $options = [
            'query' => [
                'schema' => static::SCHEMA_VERSION,
                '_service' => $service,
            ],
        ];

        return $authenticatedClient->requestAsync('GET', self::RESOLVE_ALL_URLS_URL, $options)->then(function (ResponseInterface $response) use ($service, $insecure) {
            return new static($service, \GuzzleHttp\json_decode($response->getBody(), true), $insecure);
        });
    }

    /**
     * Return a resolved URI for this service.
     *
     * @return Uri
     */
    public function resolve(): Uri
    {
        // If multiple URLs are returned, any of them are usable, so we choose
        // a random one.
        // @todo Double check this assumption.
        return $this->resolvedUrls[array_rand($this->resolvedUrls)];
    }

    /**
     * Return the service these URLs correspond to, such as 'Media Data Service'.
     *
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }
}
