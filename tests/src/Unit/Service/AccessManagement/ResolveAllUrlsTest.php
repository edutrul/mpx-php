<?php

namespace Lullabot\Mpx\Tests\Unit\Service\AccessManagement;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Lullabot\Mpx\AuthenticatedClient;
use Lullabot\Mpx\Service\AccessManagement\ResolveAllUrls;
use Lullabot\Mpx\Service\IdentityManagement\User;
use Lullabot\Mpx\Service\IdentityManagement\UserSession;
use Lullabot\Mpx\Tests\JsonResponse;
use Lullabot\Mpx\Tests\MockClientTrait;
use Lullabot\Mpx\TokenCachePool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\StoreInterface;

/**
 * Test resolving all URLs for a given MPX service.
 *
 * @coversDefaultClass \Lullabot\Mpx\Service\AccessManagement\ResolveAllUrls
 */
class ResolveAllUrlsTest extends TestCase
{
    use MockClientTrait;

    /**
     * Test basic response loading.
     *
     * @covers ::load
     * @covers ::__construct
     * @covers ::getService
     * @covers ::resolve
     */
    public function testLoad()
    {
        $client = $this->getMockClient([
            new JsonResponse(200, [], 'signin-success.json'),
            new JsonResponse(200, [], 'resolveAllUrls.json'),
        ]);
        $tokenCachePool = new TokenCachePool(new ArrayCachePool());
        /** @var StoreInterface $store */
        $store = $this->getMockBuilder(StoreInterface::class)
            ->getMock();

        $user = new User('USER-NAME', 'correct-password');
        $userSession = new UserSession($user, $client, $store, $tokenCachePool);
        $session = new AuthenticatedClient($client, $userSession);
        /** @var \Lullabot\Mpx\Service\AccessManagement\ResolveAllUrls $r */
        $r = ResolveAllUrls::load($session, 'Media Data Service')->wait();
        $this->assertEquals('Media Data Service', $r->getService());
        $this->assertEquals('http://data.media.theplatform.com/media', $r->resolve());
    }

    /**
     * Test that bad responses throw an exception.
     *
     * @covers ::__construct
     */
    public function testInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data does not contain a resolveAllUrlsResponse key and does not appear to be an MPX response.');
        new ResolveAllUrls('Media Data Service', []);
    }
}
