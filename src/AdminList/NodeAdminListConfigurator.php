<?php

namespace Hgabka\NodeBundle\AdminList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Hgabka\NodeBundle\Admin\NodeAdmin;
use Hgabka\NodeBundle\AdminList\FilterType\NodeSearchFilterType;
use Hgabka\NodeBundle\Controller\NodeAdminController;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\UtilsBundle\AdminList\Configurator\AbstractDoctrineORMAdminListConfigurator;
use Hgabka\UtilsBundle\AdminList\FilterType\ORM\BooleanFilterType;
use Hgabka\UtilsBundle\AdminList\FilterType\ORM\DateFilterType;
use Hgabka\UtilsBundle\AdminList\FilterType\ORM\StringFilterType;
use Hgabka\UtilsBundle\AdminList\ListAction\SimpleListAction;
use Hgabka\UtilsBundle\Helper\DomainConfigurationInterface;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionDefinition;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * NodeAdminListConfigurator.
 */
class NodeAdminListConfigurator extends AbstractDoctrineORMAdminListConfigurator
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $permission;

    /**
     * @var DomainConfigurationInterface
     */
    protected $domainConfiguration;

    /**
     * @var bool
     */
    protected $showAddHomepage;

    /**
     * @var Security
     */
    protected $security;

    /** @var NodeAdmin */
    protected $nodeAdmin;

    /**
     * @param EntityManager $em         The entity
     *                                  manager
     * @param AclHelper     $aclHelper  The ACL helper
     * @param string        $locale     The current
     *                                  locale
     * @param string        $permission The permission
     */
    public function __construct(EntityManager $em, AclHelper $aclHelper, $locale, $permission, Security $security, NodeAdmin $admin)
    {
        parent::__construct($em, $aclHelper);
        $this->nodeAdmin = $admin;
        $this->locale = $locale;
        $this->security = $security;
        $this->setPermissionDefinition(
            new PermissionDefinition(
                [$permission],
                Node::class,
                'n'
            )
        );
    }

    /**
     * @param \Kunstmaan\AdminBundle\Helper\DomainConfigurationInterface $domainConfiguration
     */
    public function setDomainConfiguration(DomainConfigurationInterface $domainConfiguration)
    {
        $this->domainConfiguration = $domainConfiguration;
    }

    /**
     * @param bool $showAddHomepage
     */
    public function setShowAddHomepage($showAddHomepage)
    {
        $this->showAddHomepage = $showAddHomepage;
    }

    /**
     * Build list actions ...
     */
    public function buildListActions()
    {
        if (!$this->showAddHomepage) {
            return;
        }

        $addHomepageRoute = [
            'path' => '',
            'attributes' => [
                'class' => 'btn btn-default btn--raise-on-hover',
                'data-target' => '#add-homepage-modal',
                'data-keyboard' => 'true',
                'data-toggle' => 'modal',
                'type' => 'button',
            ],
        ];

        $this->addListAction(
            new SimpleListAction(
                $addHomepageRoute,
                'hg_node.modal.add_homepage.h',
                null,
                '@HgabkaNode/Admin/list_action_button.html.twig'
            )
        );
    }

    /**
     * Configure filters.
     */
    public function buildFilters()
    {
        $this
            ->addFilter('title', new StringFilterType('title'), 'hg_node.admin.list.filter.title')
            ->addFilter('search', new NodeSearchFilterType($this->nodeAdmin->getNodeSearcher()), 'hg_node.admin.list.filter.search')
            ->addFilter('created', new DateFilterType('created'), 'hg_node.admin.list.filter.created_at')
            ->addFilter('updated', new DateFilterType('updated'), 'hg_node.admin.list.filter.updated_at')
            ->addFilter('online', new BooleanFilterType('online'), 'hg_node.admin.list.filter.online');
    }

    /**
     * Configure the visible columns.
     */
    public function buildFields()
    {
        $this
            ->addField('title', 'hg_node.admin.list.header.title', true, '@HgabkaNode/Admin/title.html.twig')
            ->addField('created', 'hg_node.admin.list.header.created_at', true)
            ->addField('updated', 'hg_node.admin.list.header.updated_at', true)
            ->addField('online', 'hg_node.admin.list.header.online', true, '@HgabkaNode/Admin/online.html.twig');
    }

    /**
     * @param mixed $item
     *
     * @return array
     */
    public function getEditUrlFor($item)
    {
        // @var Node $node
        $node = $item->getNode();

        return [
            'path' => 'HgabkaNodeBundle_nodes_edit',
            'params' => ['id' => $node->getId()],
        ];
    }

    /**
     * @return bool
     */
    public function canAdd()
    {
        return false;
    }

    public function canEdit($item)
    {
        return $this->security->isGranted(PermissionMap::PERMISSION_EDIT, $item->getNode());
    }

    /**
     * Return if current user can delete the specified item.
     *
     * @param array|object $item
     *
     * @return bool
     */
    public function canDelete($item)
    {
        return false;
    }

    /**
     * @param object $item
     *
     * @return array
     */
    public function getDeleteUrlFor($item)
    {
        return [];
    }

    /**
     * @return string
     */
    public function getBundleName(): string
    {
        return 'HgabkaNodeBundle';
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return 'NodeTranslation';
    }

    public function getEntityClass(): string
    {
        return NodeTranslation::class;
    }

    /**
     * Override path convention (because settings is a virtual admin subtree).
     *
     * @param string $suffix
     *
     * @return string
     */
    public function getPathByConvention(?string $suffix = null)
    {
        if (empty($suffix)) {
            return sprintf('%s_nodes', $this->getBundleName());
        }

        return sprintf('%s_nodes_%s', $this->getBundleName(), $suffix);
    }

    /**
     * Override controller path (because actions for different entities are
     * defined in a single Settings controller).
     *
     * @return string
     */
    public function getControllerPath()
    {
        return NodeAdminController::class;
    }

    /**
     * @param QueryBuilder $queryBuilder The query builder
     */
    public function adaptQueryBuilder(QueryBuilder $queryBuilder)
    {
        parent::adaptQueryBuilder($queryBuilder);

        $queryBuilder
            ->select('b,n')
            ->innerJoin('b.node', 'n', 'WITH', 'b.node = n.id')
            ->andWhere('b.lang = :lang')
            ->andWhere('n.deleted = 0')
            ->addOrderBy('b.updated', 'DESC')
            ->setParameter('lang', $this->locale);

        if (!$this->domainConfiguration) {
            return;
        }

        $rootNode = $this->domainConfiguration->getRootNode();
        if (null !== $rootNode) {
            $queryBuilder->andWhere('n.lft >= :left')
                         ->andWhere('n.rgt <= :right')
                         ->setParameter('left', $rootNode->getLeft())
                         ->setParameter('right', $rootNode->getRight());
        }
    }

    public function getListTitle()
    {
        return null;
    }

    /**
     * Return the url to list all the items.
     *
     * @return array
     */
    public function getIndexUrl()
    {
        $params = $this->getExtraParameters();

        return [
            'path' => $this->nodeAdmin->generateMenuUrl('list')['route'],
            'params' => $params,
        ];
    }
}
