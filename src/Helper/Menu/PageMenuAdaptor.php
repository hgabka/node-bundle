<?php

namespace Hgabka\NodeBundle\Helper\Menu;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\Node;
use hgabka\NodeBundle\Helper\NodeMenuItem;
use Hgabka\NodeBundle\Helper\PagesConfiguration;
use Hgabka\UtilsBundle\Helper\Menu\MenuAdaptorInterface;
use Hgabka\UtilsBundle\Helper\Menu\MenuBuilder;
use Hgabka\UtilsBundle\Helper\Menu\MenuItem;
use Hgabka\UtilsBundle\Helper\Menu\TopMenuItem;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclNativeHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Symfony\Component\HttpFoundation\Request;

/**
 * The Page Menu Adaptor.
 */
class PageMenuAdaptor implements MenuAdaptorInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var AclNativeHelper
     */
    private $aclNativeHelper;

    /**
     * @var array
     */
    private $treeNodes;

    /**
     * @var array
     */
    private $activeNodeIds;

    /**
     * @var PagesConfiguration
     */
    private $pagesConfiguration;

    /**
     * @param EntityManagerInterface       $em                  The entity manager
     * @param AclNativeHelper              $aclNativeHelper     The acl helper
     * @param DomainConfigurationInterface $domainConfiguration
     */
    public function __construct(
        EntityManagerInterface $em,
        AclNativeHelper $aclNativeHelper,
        PagesConfiguration $pagesConfiguration
    ) {
        $this->em = $em;
        $this->aclNativeHelper = $aclNativeHelper;
        $this->pagesConfiguration = $pagesConfiguration;
    }

    /**
     * In this method you can add children for a specific parent, but also
     * remove and change the already created children.
     *
     * @param MenuBuilder $menu      The menu builder
     * @param MenuItem[]  &$children The children array that may be adapted
     * @param MenuItem    $parent    The parent menu item
     * @param Request     $request   The request
     */
    public function adaptChildren(
        MenuBuilder $menu,
        array &$children,
        MenuItem $parent = null,
        Request $request = null
    ) {
        if (null === $parent) {
            $menuItem = new TopMenuItem($menu);
            $menuItem
                ->setRoute('admin_hgabka_node_node_list')
                ->setUniqueId('pages')
                ->setLabel('pages.title')
                ->setParent($parent);
            if (0 === stripos($request->attributes->get('_route'), 'HgabkaNodeBundle_nodes') || 'admin_hgabka_node_node_list' === $request->attributes->get('_route')) {
                $menuItem->setActive(true);
            }
            $children[] = $menuItem;
        } elseif (0 === stripos($request->attributes->get('_route'), 'HgabkaNodeBundle_nodes') || 'admin_hgabka_node_node_list' === $request->attributes->get('_route')) {
            $treeNodes = $this->getTreeNodes(
                $request->attributes->get('nodeLocale'),
                PermissionMap::PERMISSION_VIEW,
                $this->aclNativeHelper,
                true
            );
            $activeNodeIds = $this->getActiveNodeIds($request);

            if (('HgabkaNodeBundle_nodes' === $parent->getRoute() || 'admin_hgabka_node_node_list' === $parent->getRoute()) && isset($treeNodes[0])) {
                $this->processNodes(
                    $menu,
                    $children,
                    $treeNodes[0],
                    $parent,
                    $activeNodeIds
                );
            } elseif ('HgabkaNodeBundle_nodes_edit' === $parent->getRoute()) {
                $parentRouteParams = $parent->getRouteparams();
                $parent_id = $parentRouteParams['id'];
                if (\array_key_exists($parent_id, $treeNodes)) {
                    $this->processNodes(
                        $menu,
                        $children,
                        $treeNodes[$parent_id],
                        $parent,
                        $activeNodeIds
                    );
                }
            }
        }
    }

    /**
     * Get the list of nodes that is used in the admin menu.
     *
     * @param string $lang
     * @param string $permission
     * @param bool   $includeHiddenFromNav
     *
     * @return array
     */
    private function getTreeNodes(
        $lang,
        $permission,
        AclNativeHelper $aclNativeHelper,
        $includeHiddenFromNav
    ) {
        if (null === $this->treeNodes) {
            $repo = $this->em->getRepository(Node::class);
            $this->treeNodes = [];

            $rootNode = null;

            // Get all nodes that should be shown in the menu
            $allNodes = $repo->getAllMenuNodes(
                $lang,
                $permission,
                $aclNativeHelper,
                $includeHiddenFromNav,
                $rootNode
            );

            /** @var Node $nodeInfo */
            foreach ($allNodes as $nodeInfo) {
                $refEntityName = $nodeInfo['ref_entity_name'];
                if ($this->pagesConfiguration->isHiddenFromTree($refEntityName)) {
                    continue;
                }
                $parent_id = null === $nodeInfo['parent'] ? 0 : $nodeInfo['parent'];
                unset($nodeInfo['parent']);
                $this->treeNodes[$parent_id][] = $nodeInfo;
            }
            unset($allNodes);
        }

        return $this->treeNodes;
    }

    /**
     * Get an array with the id's off all nodes in the tree that should be
     * expanded.
     *
     * @param $request
     *
     * @return array
     */
    private function getActiveNodeIds($request)
    {
        if (null === $this->activeNodeIds) {
            if (0 === stripos($request->attributes->get('_route'), 'HgabkaNodeBundle_nodes_edit') || 'admin_hgabka_node_node_list' === $request->attributes->get('_route')) {
                $repo = $this->em->getRepository(Node::class);

                $currentNode = $repo->findOneById($request->attributes->get('id'));
                $parentNodes = $repo->getAllParents($currentNode);
                $this->activeNodeIds = [];
                foreach ($parentNodes as $parentNode) {
                    $this->activeNodeIds[] = (string) $parentNode->getId();
                }
            }
        }

        return null === $this->activeNodeIds ? [] : $this->activeNodeIds;
    }

    /**
     * @param MenuBuilder    $menu          The menu builder
     * @param MenuItem[]     &$children     The children array that may be
     *                                      adapted
     * @param NodeMenuItem[] $nodes         The nodes
     * @param MenuItem       $parent        The parent menu item
     * @param array          $activeNodeIds List with id's of all nodes that
     *                                      should be expanded in the tree
     */
    private function processNodes(
        MenuBuilder $menu,
        array &$children,
        array $nodes,
        MenuItem $parent = null,
        array $activeNodeIds
    ) {
        foreach ($nodes as $child) {
            $menuItem = new MenuItem($menu);
            $refName = $child['ref_entity_name'];

            $menuItem
                ->setRoute('HgabkaNodeBundle_nodes_edit')
                ->setRouteparams(['id' => $child['id']])
                ->setUniqueId('node-'.$child['id'])
                ->setLabel($child['title'])
                ->setParent($parent)
                ->setOffline(!$child['online'] && !$this->pagesConfiguration->isStructureNode($refName))
                ->setFolder($this->pagesConfiguration->isStructureNode($refName))
                ->setRole('page')
                ->setWeight($child['weight'])
                ->addAttributes(
                    [
                        'page' => [
                            'class' => $refName,
                            'children' => $this->pagesConfiguration->getPossibleChildTypes($refName),
                            'icon' => $this->pagesConfiguration->getIcon($refName),
                        ],
                    ]
                );

            if (\in_array((string) $child['id'], $activeNodeIds, true)) {
                $menuItem->setActive(true);
            }
            $children[] = $menuItem;
        }
    }
}
