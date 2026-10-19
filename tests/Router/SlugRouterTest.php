<?php

namespace Hgabka\NodeBundle\Tests\Router;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Controller\SlugController;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Repository\NodeTranslationRepository;
use Hgabka\NodeBundle\Router\SlugRouter;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;

class SlugRouterTest extends TestCase
{
    /** @var array<string, NodeTranslation> */
    private array $translations = [];

    private function router(string $strategy = 'custom', array $locales = ['hu'], string $default = 'hu', string $current = 'hu', array $patterns = ['default' => '/{url}']): SlugRouter
    {
        $repository = $this->createStub(NodeTranslationRepository::class);
        $repository->method('getNodeTranslationForUrl')->willReturnCallback(
            fn (string $url, string $locale): ?NodeTranslation => $this->translations[$locale . ':' . $url] ?? null
        );
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $container = new Container(new ParameterBag(['hgabka_node.route_config' => ['pattern' => $patterns, 'strategy' => $strategy]]));
        $container->set('doctrine.orm.entity_manager', $em);

        $utils = $this->createStub(HgabkaUtils::class);
        $utils->method('getAvailableLocales')->willReturn($locales);
        $utils->method('getDefaultLocale')->willReturn($default);
        $utils->method('getCurrentLocale')->willReturn($current);

        $router = new SlugRouter($container, $utils);
        $router->setContext(new RequestContext());

        return $router;
    }

    public function testSingleLanguageSiteHasPlainSlugAndPreviewRoutes(): void
    {
        $collection = $this->router()->getRouteCollection();

        $this->assertSame(['_slug_preview', '_slug'], array_keys($collection->all()));
        $this->assertSame('/{url}', $collection->get('_slug')->getPath());
        $this->assertSame('/preview/{url}', $collection->get('_slug_preview')->getPath());
        $this->assertSame(SlugController::class . '::slug', $collection->get('_slug')->getDefault('_controller'));
        $this->assertFalse($collection->get('_slug')->getDefault('preview'));
        $this->assertTrue($collection->get('_slug_preview')->getDefault('preview'));
        $this->assertSame('[a-zA-Z0-9\-_\/]*', $collection->get('_slug')->getRequirement('url'));
    }

    public function testPrefixStrategyAddsEveryLocale(): void
    {
        $collection = $this->router('prefix', ['hu', 'en'], 'hu')->getRouteCollection();

        $this->assertEqualsCanonicalizing(['_slug_hu', '_slug_en', '_slug_preview_hu', '_slug_preview_en'], array_keys($collection->all()));
        $this->assertSame('/en/{url}', $collection->get('_slug_en')->getPath());
        $this->assertSame('/hu/{url}', $collection->get('_slug_hu')->getPath());
        $this->assertSame('/en/preview/{url}', $collection->get('_slug_preview_en')->getPath());
    }

    public function testPrefixExceptDefaultLeavesTheDefaultLocaleUnprefixed(): void
    {
        $collection = $this->router('prefix_except_default', ['hu', 'en'], 'hu')->getRouteCollection();

        $this->assertEqualsCanonicalizing(['_slug_en', '_slug', '_slug_preview_en', '_slug_preview'], array_keys($collection->all()));
        $this->assertSame('/{url}', $collection->get('_slug')->getPath());
        $this->assertSame('/en/{url}', $collection->get('_slug_en')->getPath());
    }

    public function testCustomStrategyWithManyLocalesUsesThePatternWithoutPrefixes(): void
    {
        $collection = $this->router('custom', ['hu', 'en'], 'hu', 'hu', ['default' => '/{_locale}/{url}'])->getRouteCollection();

        $this->assertSame(['_slug_preview', '_slug'], array_keys($collection->all()));
        $this->assertSame('/{url}', $collection->get('_slug')->getPath());
    }

    public function testLocalePlaceholderIsRemovedForSingleLocaleSites(): void
    {
        $collection = $this->router('custom', ['hu'], 'hu', 'hu', ['default' => '/{_locale}/{url}'])->getRouteCollection();

        $this->assertSame('/{url}', $collection->get('_slug')->getPath());
    }

    public function testLocaleSpecificPatternWinsOverDefault(): void
    {
        $collection = $this->router('custom', ['hu'], 'hu', 'hu', ['default' => '/{url}', 'hu' => '/oldal/{url}'])->getRouteCollection();

        $this->assertSame('/oldal/{url}', $collection->get('_slug')->getPath());
    }

    public function testGenerateSlugUrls(): void
    {
        $this->assertSame('/rolunk', $this->router()->generate('_slug', ['url' => 'rolunk']));
        $this->assertSame('/preview/rolunk', $this->router()->generate('_slug_preview', ['url' => 'rolunk']));
    }

    public function testGenerateWithPrefixStrategy(): void
    {
        $router = $this->router('prefix', ['hu', 'en'], 'hu', 'hu');

        $this->assertSame('/hu/rolunk', $router->generate('_slug', ['url' => 'rolunk']));
        $this->assertSame('/en/about', $router->generate('_slug', ['url' => 'about', '_locale' => 'en']));
    }

    public function testGenerateWithPrefixExceptDefaultSkipsThePrefixForTheDefaultLocale(): void
    {
        $router = $this->router('prefix_except_default', ['hu', 'en'], 'hu', 'hu');

        $this->assertSame('/rolunk', $router->generate('_slug', ['url' => 'rolunk']));
        $this->assertSame('/rolunk', $router->generate('_slug', ['url' => 'rolunk', '_locale' => 'hu']));
        $this->assertSame('/en/about', $router->generate('_slug', ['url' => 'about', '_locale' => 'en']));
    }

    public function testGenerateOfUnknownRouteFails(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->router()->generate('nope');
    }

    public function testMatchResolvesTheNodeTranslation(): void
    {
        $translation = (new NodeTranslation())->setLang('hu')->setUrl('rolunk');
        $this->translations['hu:rolunk'] = $translation;

        $match = $this->router()->match('/rolunk');

        $this->assertSame($translation, $match['_nodeTranslation']);
        $this->assertSame('hu', $match['_locale']);
        $this->assertSame('_slug', $match['_route']);
        $this->assertSame('rolunk', $match['url']);
    }

    public function testMatchOverridesTheLocaleWithTheTranslationsLanguage(): void
    {
        $this->translations['hu:about'] = (new NodeTranslation())->setLang('en')->setUrl('about');

        $this->assertSame('en', $this->router()->match('/about')['_locale']);
    }

    public function testMatchOfAnUnknownPageIsNotFoundSoOtherRoutersCanTakeOver(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No page found for slug /nincs');
        $this->router()->match('/nincs');
    }

    public function testSupportsOnlySlugRoutes(): void
    {
        $router = $this->router();

        $this->assertTrue($router->supports('_slug'));
        $this->assertTrue($router->supports('_slug_preview_en'));
        $this->assertFalse($router->supports('homepage'));
        $this->assertSame('Node bundle route', $router->getRouteDebugMessage('_slug'));
    }

    public function testContext(): void
    {
        $router = $this->router();
        $context = new RequestContext('', 'POST');

        $router->setContext($context);

        $this->assertSame($context, $router->getContext());
    }
}
