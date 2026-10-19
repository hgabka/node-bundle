<?php

namespace Hgabka\NodeBundle\Tests\DependencyInjection;

use Hgabka\NodeBundle\DependencyInjection\Configuration;
use Hgabka\NodeBundle\DependencyInjection\HgabkaNodeExtension;
use Hgabka\NodeBundle\Helper\PagesConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ExtensionTest extends TestCase
{
    private function process(array $config = []): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('twig.form.resources', ['form_div_layout.html.twig']);

        return $container;
    }

    public function testDefaults(): void
    {
        $config = $this->process();

        $this->assertSame(['default' => '/{url}'], $config['route']['pattern']);
        $this->assertSame('prefix_except_default', $config['route']['strategy']);
        $this->assertSame('15', $config['publish_later_stepping']);
        $this->assertSame('15', $config['unpublish_later_stepping']);
        $this->assertSame('App\Entity\User', $config['user_entity_class']);
        $this->assertTrue($config['show_add_homepage']);
        $this->assertFalse($config['lock']['enabled']);
        $this->assertSame([15, 35], [$config['lock']['check_interval'], $config['lock']['threshold']]);
    }

    public function testStringRoutePatternBecomesTheDefaultPattern(): void
    {
        $config = $this->process(['route' => ['pattern' => '/{_locale}/{url}']]);

        $this->assertSame(['default' => '/{_locale}/{url}'], $config['route']['pattern']);
    }

    public function testAllowedChildrenCanBeGivenAsClassNames(): void
    {
        $config = $this->process(['pages' => ['App\Home' => ['name' => 'Home', 'allowed_children' => ['App\Page', ['class' => 'App\Other', 'name' => 'Other']]]]]);

        $this->assertSame([['class' => 'App\Page'], ['class' => 'App\Other', 'name' => 'Other']], $config['pages']['App\Home']['allowed_children']);
    }

    public function testPageNameIsRequired(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->process(['pages' => ['App\Home' => ['icon' => 'x']]]);
    }

    public function testParametersAndPagesConfigurationAreRegistered(): void
    {
        $container = $this->container();

        (new HgabkaNodeExtension())->load([[
            'show_add_homepage' => false,
            'user_entity_class' => 'App\Entity\Admin',
            'lock' => ['enabled' => true, 'check_interval' => 5, 'threshold' => 20],
            'pages' => ['App\Home' => ['name' => 'Home']],
        ]], $container);

        $this->assertFalse($container->getParameter('hgabka_node.show_add_homepage'));
        $this->assertSame('App\Entity\Admin', $container->getParameter('hgabka_node.user_entity_class'));
        $this->assertTrue($container->getParameter('hgabka_node.lock_enabled'));
        $this->assertSame([5, 20], [$container->getParameter('hgabka_node.lock_check_interval'), $container->getParameter('hgabka_node.lock_threshold')]);
        $this->assertSame('/{url}', $container->getParameter('hgabka_node.route_config')['pattern']['default']);
        $this->assertSame(PagesConfiguration::class, $container->getDefinition('hgabka_node.pages_configuration')->getClass());
        $this->assertSame('Home', $container->getDefinition('hgabka_node.pages_configuration')->getArgument(0)['App\Home']['name']);
        $this->assertContains('@HgabkaNode/Form/formWidgets.html.twig', $container->getParameter('twig.form.resources'));
    }

    public function testPrependConfiguresTheCmfRouterAndTwigGlobals(): void
    {
        $container = new ContainerBuilder();
        $container->prependExtensionConfig('hgabka_node', ['publish_later_stepping' => '30']);

        (new HgabkaNodeExtension())->prepend($container);

        $this->assertSame(
            ['chain' => ['routers_by_id' => ['router.default' => 100], 'replace_symfony_router' => true]],
            $container->getExtensionConfig('cmf_routing')[0]
        );
        $globals = $container->getExtensionConfig('twig')[0]['globals'];
        $this->assertTrue($globals['nodebundleisactive']);
        $this->assertSame('30', $globals['publish_later_stepping']);
        $this->assertSame('15', $globals['unpublish_later_stepping']);
    }

    public function testCompilerPassPointsTheRouterAliasAtTheCmfRouter(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('cmf_routing.router', new Definition('stdClass'));

        (new HgabkaNodeExtension())->process($container);

        $this->assertSame('cmf_routing.router', (string) $container->getAlias('router'));
        $this->assertTrue($container->getAlias('router')->isPublic());
    }
}
