<?php

namespace Kunstmaan\NodeBundle\Tests\Router;

use Kunstmaan\NodeBundle\Entity\NodeTranslation;
use Kunstmaan\NodeBundle\Router\SlugRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @coversNothing
 */
class SlugRouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::__construct
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::generate
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getRouteCollection
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::addPreviewRoute
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getPreviewRouteParameters
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getEscapedLocales
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::addSlugRoute
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getSlugRouteParameters
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getContext
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getDefaultLocale
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::isMultiLanguage
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getBackendLocales
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getFrontendLocales
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::addRoute
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getSlugPattern
     */
    public function testGenerateMultiLanguage()
    {
        $request = $this->getRequest();
        $container = $this->getContainer($request, true);
        $object = new SlugRouter($container);
        $url = $object->generate('_slug', ['url' => 'some-uri', '_locale' => 'en'], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('http://domain.tld/en/some-uri', $url);

        $url = $object->generate('_slug', ['url' => 'some-uri', '_locale' => 'en'], UrlGeneratorInterface::ABSOLUTE_PATH);
        $this->assertSame('/en/some-uri', $url);
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::generate
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getRouteCollection
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getContext
     */
    public function testGenerateSingleLanguage()
    {
        $request = $this->getRequest();
        $container = $this->getContainer($request);
        $object = new SlugRouter($container);
        $url = $object->generate('_slug', ['url' => 'some-uri', '_locale' => 'nl'], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('http://domain.tld/some-uri', $url);

        $url = $object->generate('_slug', ['url' => 'some-uri', '_locale' => 'nl'], UrlGeneratorInterface::ABSOLUTE_PATH);
        $this->assertSame('/some-uri', $url);
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::setContext
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getContext
     */
    public function testSetContext()
    {
        $context = $this->getMock('Symfony\Component\Routing\RequestContext');
        $container = $this->getContainer(null);
        $object = new SlugRouter($container);
        $object->setContext($context);
        $this->assertSame($context, $object->getContext());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::match
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getRouteCollection
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getContext
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getNodeTranslation
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getMasterRequest
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::getNodeTranslationRepository
     */
    public function testMatchWithNodeTranslation()
    {
        $request = $this->getRequest();
        $nodeTranslation = new NodeTranslation();
        $container = $this->getContainer($request, true, $nodeTranslation);
        $object = new SlugRouter($container);
        $result = $object->match('/en/some-uri');
        $this->assertSame('some-uri', $result['url']);
        $this->assertSame('en', $result['_locale']);
        $this->assertSame($nodeTranslation, $result['_nodeTranslation']);
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Router\SlugRouter::match
     */
    public function testMatchWithoutNodeTranslation()
    {
        $request = $this->getRequest();
        $container = $this->getContainer($request);
        $object = new SlugRouter($container);
        $object->match('/en/some-uri');
    }

    private function getContainer($request, $multiLanguage = false, $nodeTranslation = null)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $serviceMap = [
            ['request_stack', 1, $this->getRequestStack($request)],
            ['kunstmaan_admin.domain_configuration', 1, $this->getDomainConfiguration($multiLanguage)],
            ['doctrine.orm.entity_manager', 1, $this->getEntityManager($nodeTranslation)],
        ];

        $container
            ->method('get')
            ->will($this->returnValueMap($serviceMap));

        return $container;
    }

    private function getRequestStack($request)
    {
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getMasterRequest')->willReturn($request);

        return $requestStack;
    }

    private function getDomainConfiguration($multiLanguage = false)
    {
        $domainConfiguration = $this->getMock('Kunstmaan\AdminBundle\Helper\DomainConfigurationInterface');
        $domainConfiguration->method('getHost')
            ->willReturn('domain.tld');

        $domainConfiguration->method('isMultiDomainHost')
            ->willReturn(false);

        $domainConfiguration->method('isMultiLanguage')
            ->willReturn($multiLanguage);

        $domainConfiguration->method('getDefaultLocale')
            ->willReturn('nl');

        $domainConfiguration->method('getFrontendLocales')
            ->willReturn($multiLanguage ? ['nl', 'en'] : ['nl']);

        $domainConfiguration->method('getBackendLocales')
            ->willReturn($multiLanguage ? ['nl', 'en'] : ['nl']);

        $domainConfiguration->method('getRootNode')
            ->willReturn(null);

        return $domainConfiguration;
    }

    private function getRequest($url = 'http://domain.tld/')
    {
        $request = Request::create($url);

        return $request;
    }

    private function getEntityManager($nodeTranslation = null)
    {
        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em
            ->method('getRepository')
            ->with($this->equalTo('KunstmaanNodeBundle:NodeTranslation'))
            ->willReturn($this->getNodeTranslationRepository($nodeTranslation));

        return $em;
    }

    private function getNodeTranslationRepository($nodeTranslation = null)
    {
        $repository = $this->getMockBuilder('Kunstmaan\NodeBundle\Repository\NodeTranslationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository
            ->method('getNodeTranslationForUrl')
            ->willReturn($nodeTranslation);

        return $repository;
    }
}
