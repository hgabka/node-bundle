<?php

namespace Kunstmaan\NodeBundle\Tests\Helper\Menu;

use Knp\Menu\Integration\Symfony\RoutingExtension;
use Knp\Menu\MenuFactory;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\NodeTranslation;
use Kunstmaan\NodeBundle\Entity\NodeVersion;
use Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder;
use Kunstmaan\NodeBundle\Helper\PagesConfiguration;
use Kunstmaan\NodeBundle\Tests\Stubs\TestRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @coversNothing
 */
class ActionsMenuBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionsMenuBuilder
     */
    protected $builder;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::__construct
     */
    protected function setUp()
    {
        // @var UrlGeneratorInterface $urlGenerator
        $urlGenerator = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $routingExtension = new RoutingExtension($urlGenerator);
        $factory = new MenuFactory();
        $factory->addExtension($routingExtension);
        $em = $this->getMockedEntityManager();
        // @var EventDispatcherInterface $dispatcher
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        // @var RouterInterface $router
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $authorizationChecker = $this->getMock('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface');
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        $this->builder = new ActionsMenuBuilder($factory, $em, $router, $dispatcher, $authorizationChecker, new PagesConfiguration([]));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::createSubActionsMenu
     */
    public function testCreateSubActionsMenu()
    {
        $nodeTranslation = new NodeTranslation();
        $nodeTranslation->setNode(new Node());

        $nodeVersion = new NodeVersion();
        $nodeVersion->setNodeTranslation($nodeTranslation);

        $this->builder->setActiveNodeVersion($nodeVersion);

        $menu = $this->builder->createSubActionsMenu();
        $this->assertNotNull($menu->getChild('subaction.versions'));

        $this->assertSame('page-sub-actions', $menu->getChildrenAttribute('class'));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::createActionsMenu
     */
    public function testCreateActionsMenuDraft()
    {
        $nodeTranslation = new NodeTranslation();
        $nodeTranslation->setNode(new Node());

        $nodeVersion = new NodeVersion();
        $nodeVersion->setType('draft');
        $nodeVersion->setNodeTranslation($nodeTranslation);

        $this->builder->setActiveNodeVersion($nodeVersion);

        $menu = $this->builder->createActionsMenu();
        $this->assertNotNull($menu->getChild('action.saveasdraft'));
        $this->assertNull($menu->getChild('action.recopyfromlanguage'));
        $this->assertNotNull($menu->getChild('action.publish'));
        $this->assertNotNull($menu->getChild('action.preview'));
        $this->assertNull($menu->getChild('action.save'));

        if ((null !== $nodeTranslation->getNode()->getParent() || $nodeTranslation->getNode()->getChildren()->isEmpty())) {
            $this->assertNotNull($menu->getChild('action.delete'));
        } else {
            $this->assertNull($menu->getChild('action.delete'));
        }

        $this->assertSame('page-main-actions js-auto-collapse-buttons', $menu->getChildrenAttribute('class'));
    }

    /**
     * testCreateActionsMenuPublic.
     */
    public function testCreateActionsMenuPublic()
    {
        $nodeTranslation = new NodeTranslation();
        $nodeTranslation->setNode(new Node());

        $nodeVersion = new NodeVersion();
        $nodeVersion->setType('public');
        $nodeVersion->setNodeTranslation($nodeTranslation);

        $this->builder->setActiveNodeVersion($nodeVersion);

        $menu = $this->builder->createActionsMenu();
        $this->assertNotNull($menu->getChild('action.save'));
        $this->assertNotNull($menu->getChild('action.saveasdraft'));
        $this->assertNull($menu->getChild('action.recopyfromlanguage'));
        $this->assertNotNull($menu->getChild('action.preview'));
        $this->assertNotNull($menu->getChild('action.publish'));
        $this->assertNull($menu->getChild('action.unpublish'));
        if ((null !== $nodeTranslation->getNode()->getParent() || $nodeTranslation->getNode()->getChildren()->isEmpty())) {
            $this->assertNotNull($menu->getChild('action.delete'));
        } else {
            $this->assertNull($menu->getChild('action.delete'));
        }

        $nodeTranslation->setOnline(true);
        $menu = $this->builder->createActionsMenu();
        $this->assertNotNull($menu->getChild('action.save'));
        $this->assertNotNull($menu->getChild('action.saveasdraft'));
        $this->assertNull($menu->getChild('action.recopyfromlanguage'));
        $this->assertNotNull($menu->getChild('action.preview'));
        $this->assertNull($menu->getChild('action.publish'));
        $this->assertNotNull($menu->getChild('action.unpublish'));
        if ((null !== $nodeTranslation->getNode()->getParent() || $nodeTranslation->getNode()->getChildren()->isEmpty())) {
            $this->assertNotNull($menu->getChild('action.delete'));
        } else {
            $this->assertNull($menu->getChild('action.delete'));
        }

        $this->assertSame('page-main-actions js-auto-collapse-buttons', $menu->getChildrenAttribute('class'));
    }

    /**
     * testCreateActionsMenuNonEditable.
     */
    public function testCreateActionsMenuNonEditable()
    {
        $nodeTranslation = new NodeTranslation();
        $nodeTranslation->setNode(new Node());

        $nodeVersion = new NodeVersion();
        $nodeVersion->setType('public');
        $nodeVersion->setNodeTranslation($nodeTranslation);
        $this->builder->setEditableNode(false);

        $this->builder->setActiveNodeVersion($nodeVersion);
        $nodeTranslation->setOnline(false);

        $menu = $this->builder->createActionsMenu();
        $this->assertNotNull($menu->getChild('action.save')); // We want to save.
        $this->assertNull($menu->getChild('action.saveasdraft'));
        $this->assertNull($menu->getChild('action.recopyfromlanguage'));
        $this->assertNull($menu->getChild('action.preview'));
        $this->assertNull($menu->getChild('action.publish'));
        $this->assertNull($menu->getChild('action.unpublish'));

        $this->assertSame('page-main-actions js-auto-collapse-buttons', $menu->getChildrenAttribute('class'));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::createTopActionsMenu
     */
    public function testCreateTopActionsMenu()
    {
        $nodeTranslation = new NodeTranslation();
        $nodeTranslation->setNode(new Node());

        $nodeVersion = new NodeVersion();
        $nodeVersion->setNodeTranslation($nodeTranslation);

        $this->builder->setActiveNodeVersion($nodeVersion);

        $menu = $this->builder->createTopActionsMenu();
        $this->assertSame('page-main-actions page-main-actions--top', $menu->getChildrenAttribute('class'));
        $this->assertSame('page-main-actions-top', $menu->getChildrenAttribute('id'));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::setActiveNodeVersion
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::getActiveNodeVersion
     */
    public function testSetGetActiveNodeVersion()
    {
        $nodeVersion = new NodeVersion();
        $this->builder->setActiveNodeVersion($nodeVersion);
        $this->assertSame($this->builder->getActiveNodeVersion(), $nodeVersion);
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::createActionsMenu
     */
    public function testShouldShowDeleteButtonWhenTheNodeHasAParent()
    {
        $nodeTranslation = new NodeTranslation();
        $node = new Node();
        $node->setParent(new Node());
        $nodeTranslation->setNode($node);

        $nodeVersion = new NodeVersion();
        $nodeVersion->setType('public');
        $nodeVersion->setNodeTranslation($nodeTranslation);

        $this->builder->setActiveNodeVersion($nodeVersion);

        $menu = $this->builder->createActionsMenu();
        $this->assertNotNull($menu->getChild('action.delete'));

        $this->assertSame('page-main-actions js-auto-collapse-buttons', $menu->getChildrenAttribute('class'));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Helper\Menu\ActionsMenuBuilder::createActionsMenu
     */
    public function testShouldShowRecopyButtonWhenTheNodeHasTranslations()
    {
        $node = new Node();
        $nodeTranslation = new NodeTranslation();
        $nodeTranslation->setLang('en');

        $node->addNodeTranslation($nodeTranslation);

        $nodeVersion = new NodeVersion();
        $nodeVersion->setType('public');
        $nodeVersion->setNodeTranslation($nodeTranslation);

        $this->builder->setActiveNodeVersion($nodeVersion);

        $nodeTranslation = new NodeTranslation();
        $nodeTranslation->setLang('nl');

        $node->addNodeTranslation($nodeTranslation);

        $nodeVersion = new NodeVersion();
        $nodeVersion->setType('public');
        $nodeVersion->setNodeTranslation($nodeTranslation);

        $this->builder->setActiveNodeVersion($nodeVersion);

        $menu = $this->builder->createActionsMenu();
        $this->assertNotNull($menu->getChild('action.recopyfromlanguage'));

        $this->assertSame('page-main-actions js-auto-collapse-buttons', $menu->getChildrenAttribute('class'));
    }

    /**
     * https://gist.github.com/1331789.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getMockedEntityManager()
    {
        $emMock = $this->getMock(
            '\Doctrine\ORM\EntityManager',
            ['getRepository', 'getClassMetadata', 'persist', 'flush'],
            [],
            '',
            false
        );
        $emMock->expects($this->any())
            ->method('getRepository')
            ->willReturn(new TestRepository());
        $emMock->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn((object) ['name' => 'aClass']);
        $emMock->expects($this->any())
            ->method('persist')
            ->willReturn(null);
        $emMock->expects($this->any())
            ->method('flush')
            ->willReturn(null);

        return $emMock;  // it tooks 13 lines to achieve mock!
    }
}
