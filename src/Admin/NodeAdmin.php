<?php

namespace Hgabka\NodeBundle\Admin;

use Doctrine\ORM\QueryBuilder;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionDefinition;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;

class NodeAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'cms';

    public function createQuery($context = 'list')
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

        // Apply ACL restrictions (if applicable)
        if (null !== $permissionDef && null !== $aclHelper) {
            $query = $aclHelper->apply($queryBuilder, $permissionDef);
        } else {
            $query = $queryBuilder->getQuery();
        }

        return $query;
    }

    public function configureRoutes(RouteCollection $collection)
    {
        $collection->add('edit_custom', $this->getRouterIdParameter().'/editCustom');
    }
}
