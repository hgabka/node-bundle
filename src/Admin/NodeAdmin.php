<?php

namespace Hgabka\NodeBundle\Admin;

use Doctrine\ORM\QueryBuilder;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionDefinition;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class NodeAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'cms';

    protected $accessMapping = [
        'copy' => 'COPY',
        'duplicate' => 'DUPLICATE',
        'publish' => 'PUBLISH',
        'unpublish' => 'UNPUBLISH',
        'revert' => 'REVERT',
        'reorder' => 'REORDER',
    ];

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getConfigurationPool()->getContainer()->get('doctrine')->getRepository(NodeTranslation::class)->createQueryBuilder('b');
        $queryBuilder
            ->select('b,n')
            ->innerJoin('b.node', 'n', 'WITH', 'b.node = n.id')
            ->andWhere('b.lang = :lang')
            ->andWhere('n.deleted = 0')
            ->addOrderBy('b.updated', 'DESC')
            ->setParameter('lang', $this->getConfigurationPool()->getContainer()->get(HgabkaUtils::class)->getAdminLocale())
        ;

        $aclHelper = $this->getConfigurationPool()->getContainer()->get('hgabka_utils.acl.helper');
        $permission = PermissionMap::PERMISSION_VIEW;
        $permissionDef = new PermissionDefinition([$permission], Node::class, 'n');

        $query = new ProxyQuery($queryBuilder);
        // Apply ACL restrictions (if applicable)
        if (null !== $permissionDef && null !== $aclHelper) {
            $query = $aclHelper->applyToProxyQuery($query, $permissionDef);
        }

        return $query;
    }

    public function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('edit_custom', $this->getRouterIdParameter().'/editCustom');
    }

    /**
     * Get the list of actions that can be accessed directly from the dashboard.
     *
     * @return array
     */
    protected function configureDashboardActions(array $actions): array
    {
        $actions = [];

        if ($this->hasAccess('list')) {
            $actions['list'] = [
                'label' => 'hg_node.admin.node.list',
                'translation_domain' => 'messages',
                'url' => $this->generateUrl('list'),
                'icon' => 'fas fa-list',
            ];
        }

        return $actions;
    }
}
