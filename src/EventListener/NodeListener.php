<?php

namespace Hgabka\NodeBundle\EventListener;

use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Event\AdaptFormEvent;
use Hgabka\NodeBundle\Helper\FormWidgets\PermissionsFormWidget;
use Hgabka\UtilsBundle\Helper\FormWidgets\Tabs\Tab;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionAdmin;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NodeListener
{
    /**
     * @param AuthorizationCheckerInterface $authorizationChecker The security context
     * @param PermissionAdmin               $permissionAdmin      The permission admin
     * @param PermissionMapInterface        $permissionMap        The permission map
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker, 
        private readonly PermissionAdmin $permissionAdmin, 
        private readonly PermissionMapInterface $permissionMap
    ) {}

    public function adaptForm(AdaptFormEvent $event)
    {
        if ($event->getPage() instanceof HasNodeInterface && !$event->getPage()->isStructureNode()) {
            if ($this->authorizationChecker->isGranted('ROLE_PERMISSIONMANAGER')) {
                $tabPane = $event->getTabPane();
                $tabPane->addTab(new Tab('hg_node.tab.permissions.title', new PermissionsFormWidget($event->getPage(), $event->getNode(), $this->permissionAdmin, $this->permissionMap)));
            }
        }
    }
}
