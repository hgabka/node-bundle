<?php

namespace Hgabka\NodeBundle\Repository;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Helper\HiddenFromNavInterface;
use Hgabka\UtilsBundle\Helper\ClassLookup;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclNativeHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionDefinition;

/**
 * NodeRepository.
 */
class NodeRepository extends NestedTreeRepository
{
    /**
     * @param string    $lang                 The locale
     * @param string    $permission           The permission (read, write, ...)
     * @param AclHelper $aclHelper            The acl helper
     * @param bool      $includeHiddenFromNav include the hiddenfromnav nodes
     *                                        or not
     *
     * @return Node[]
     */
    public function getTopNodes(
        $lang,
        $permission,
        AclHelper $aclHelper,
        $includeHiddenFromNav = false
    ) {
        $result = $this->getChildNodes(
            null,
            $lang,
            $permission,
            $aclHelper,
            $includeHiddenFromNav
        );

        return $result;
    }

    /**
     * @param null|int    $parentId                      The parent node id
     * @param string      $lang                          The locale
     * @param bool        $includeHiddenFromNav          Include nodes hidden from navigation or not
     * @param bool        $includeHiddenWithInternalName
     * @param null|Node   $rootNode                      Root node of the current tree
     * @param null|string $refEntityName
     * @param mixed       $includeOffline
     *
     * @return Node[]
     */
    public function getChildNodesQueryBuilder(
        $parentId,
        $lang,
        $includeHiddenFromNav = false,
        $includeHiddenWithInternalName = false,
        $rootNode = null,
        $refEntityName = null,
        $includeOffline = true
    ) {
        $qb = $this->createQueryBuilder('b')
                   ->select('b', 't', 'v')
                   ->leftJoin('b.nodeTranslations', 't', 'WITH', 't.lang = :lang')
                   ->leftJoin(
                       't.publicNodeVersion',
                       'v',
                       'WITH',
                       't.publicNodeVersion = v.id'
                   )
                   ->where('b.deleted = 0')
                   ->setParameter('lang', $lang)
        ;
        if (!$includeHiddenFromNav) {
            if ($includeHiddenWithInternalName) {
                $qb->andWhere(
                    '(b.hiddenFromNav != true OR b.internalName IS NOT NULL)'
                );
            } else {
                $qb->andWhere('b.hiddenFromNav != true');
            }
        }

        if (null === $parentId) {
            $qb->andWhere('b.parent is NULL');
        } elseif (false !== $parentId) {
            $qb->andWhere('b.parent = :parent')
               ->setParameter('parent', $parentId);
        }
        if ($rootNode) {
            $qb->andWhere('b.lft >= :left')
               ->andWhere('b.rgt <= :right')
               ->setParameter('left', $rootNode->getLeft())
               ->setParameter('right', $rootNode->getRight());
        }

        if (null !== $refEntityName) {
            if (is_array($refEntityName)) {
                $qb
                    ->andWhere('v.refEntityName IN (:refEntityNames)')
                    ->setParameter('refEntityNames', $refEntityName);
            } else {
                $qb
                    ->andWhere('v.refEntityName = :refEntityName')
                    ->setParameter('refEntityName', $refEntityName);
            }
        }

        if (!$includeOffline) {
            $qb->andWhere('t.online = 1');
        }

        return $qb;
    }

    /**
     * @param null|int    $parentId                      The parent node id
     * @param string      $lang                          The locale
     * @param string      $permission                    The permission (read, write, ...)
     * @param AclHelper   $aclHelper                     The acl helper
     * @param bool        $includeHiddenFromNav          Include nodes hidden from navigation or not
     * @param bool        $includeHiddenWithInternalName
     * @param null|Node   $rootNode                      Root node of the current tree
     * @param null|string $refEntityName
     * @param mixed       $includeOffline
     *
     * @return Node[]
     */
    public function getChildNodes(
        $parentId,
        $lang,
        $permission,
        AclHelper $aclHelper,
        $includeHiddenFromNav = false,
        $includeHiddenWithInternalName = false,
        $rootNode = null,
        $refEntityName = null,
        $includeOffline = true
    ) {
        $qb = $this->getChildNodesQueryBuilder($parentId, $lang, $includeHiddenFromNav, $includeHiddenWithInternalName, $rootNode, $refEntityName, $includeOffline);
        $qb
            ->addOrderBy('t.weight', 'ASC')
            ->addOrderBy('t.title', 'ASC');

        $query = $aclHelper->apply(
            $qb,
            new PermissionDefinition([$permission])
        );

        return $query->getResult();
    }

    /**
     * @return null|Node
     */
    public function getNodeFor(HasNodeInterface $hasNode)
    {
        // @var NodeVersion $nodeVersion
        $nodeVersion = $this->getEntityManager()->getRepository(
            NodeVersion::class
        )->getNodeVersionFor(
            $hasNode
        );
        if (null !== $nodeVersion) {
            // @var NodeTranslation $nodeTranslation
            $nodeTranslation = $nodeVersion->getNodeTranslation();
            if (null !== $nodeTranslation) {
                return $nodeTranslation->getNode();
            }
        }

        return null;
    }

    /**
     * @param int    $id         The id
     * @param string $entityName The class name
     *
     * @return null|Node
     */
    public function getNodeForIdAndEntityname($id, $entityName)
    {
        // @var NodeVersion $nodeVersion
        $nodeVersion = $this->getEntityManager()->getRepository(
            NodeVersion::class
        )->findOneBy(
            ['refId' => $id, 'refEntityName' => $entityName]
        );
        if ($nodeVersion) {
            return $nodeVersion->getNodeTranslation()->getNode();
        }

        return null;
    }

    /**
     * @param Node   $parentNode The parent node (may be null)
     * @param string $slug       The slug
     *
     * @return null|Node
     */
    public function getNodeForSlug(Node $parentNode, $slug)
    {
        $slugParts = explode('/', $slug);
        $result = null;
        foreach ($slugParts as $slugPart) {
            if ($parentNode) {
                if ($r = $this->findOneBy(
                    [
                        'slug' => $slugPart,
                        'parent.parent' => $parentNode->getId(),
                    ]
                )
                ) {
                    $result = $r;
                }
            } else {
                if ($r = $this->findOneBy(['slug' => $slugPart])) {
                    $result = $r;
                }
            }
        }

        return $result;
    }

    /**
     * @param HasNodeInterface $hasNode      The object to link to
     * @param string           $lang         The locale
     * @param object           $owner        The user
     * @param string           $internalName The internal name (may be null)
     *
     * @throws \InvalidArgumentException
     *
     * @return Node
     */
    public function createNodeFor(
        HasNodeInterface $hasNode,
        $lang,
        $owner,
        $internalName = null
    ) {
        $em = $this->getEntityManager();
        $node = new Node();
        $node->setRef($hasNode);
        if (!$hasNode->getId() > 0) {
            throw new \InvalidArgumentException('the entity of class ' . $node->getRefEntityName() . ' has no id, maybe you forgot to flush first');
        }
        $node->setDeleted(false);
        $node->setInternalName($internalName);
        $parent = $hasNode->getParent();
        if ($parent) {
            // @var NodeVersion $parentNodeVersion
            $parentNodeVersion = $em->getRepository(
                NodeVersion::class
            )->findOneBy(
                [
                    'refId' => $parent->getId(),
                    'refEntityName' => ClassLookup::getClass($parent),
                ]
            );
            if ($parentNodeVersion) {
                $node->setParent(
                    $parentNodeVersion->getNodeTranslation()->getNode()
                );
            }
        }
        if ($hasNode instanceof HiddenFromNavInterface) {
            $node->setHiddenFromNav($hasNode->isHiddenFromNav());
        }
        $em->persist($node);
        $em->flush();
        $em->refresh($node);
        $em->getRepository(NodeTranslation::class)
            ->createNodeTranslationFor(
                $hasNode,
                $lang,
                $node,
                $owner
            );

        return $node;
    }

    /**
     * Get all the information needed to build a menu tree with one query.
     * We only fetch the fields we need, instead of fetching full objects to
     * limit the memory usage.
     *
     * @param string          $lang                 The locale
     * @param string          $permission           The permission (read,
     *                                              write, ...)
     * @param AclNativeHelper $aclNativeHelper      The acl helper
     * @param bool            $includeHiddenFromNav Include nodes hidden from
     *                                              navigation or not
     * @param Node            $rootNode             The root node of the
     *                                              current site
     *
     * @return array
     */
    public function getAllMenuNodes(
        $lang,
        $permission,
        AclNativeHelper $aclNativeHelper,
        $includeHiddenFromNav = false,
        ?Node $rootNode = null
    ) {
        $connection = $this->_em->getConnection();
        $qb = $connection->createQueryBuilder();
        $databasePlatform = $connection->getDatabasePlatform();
        $createIfStatement = function (
            $expression,
            $trueValue,
            $falseValue
        ) use ($databasePlatform) {
            $statement = match (true) {
                $databasePlatform instanceof SqlitePlatform => 'CASE WHEN %s THEN %s ELSE %s END',
                default => 'IF(%s, %s, %s)'
            };

            return sprintf($statement, $expression, $trueValue, $falseValue);
        };

        $sql = <<<SQL
            n.id, n.parent_id AS parent, t.url, t.id AS nt_id,
            {$createIfStatement('t.weight IS NULL', 'v.weight', 't.weight')} AS weight,
            {$createIfStatement('t.title IS NULL', 'v.title', 't.title')} AS title,
            {$createIfStatement('t.online IS NULL', '0', 't.online')} AS online,
            n.hidden_from_nav AS hidden,
            n.ref_entity_name AS ref_entity_name
            SQL;

        $qb->select($sql)
            ->from('hg_node_nodes', 'n')
            ->leftJoin(
                'n',
                'hg_node_node_translations',
                't',
                '(t.node_id = n.id AND t.lang = :lang)'
            )
            ->leftJoin(
                'n',
                'hg_node_node_translations',
                'v',
                '(v.node_id = n.id AND v.lang <> :lang)'
            )
            ->where('n.deleted = 0')
            ->addGroupBy('n.id')
            ->addOrderBy('t.weight', 'ASC')
            ->addOrderBy('t.title', 'ASC');

        if (!$includeHiddenFromNav) {
            $qb->andWhere('n.hidden_from_nav <> 0');
        }

        if (null !== $rootNode) {
            $qb->andWhere('n.lft >= :left')
                ->andWhere('n.rgt <= :right');
        }

        $permissionDef = new PermissionDefinition([$permission]);
        $permissionDef->setEntity(Node::class);
        $permissionDef->setAlias('n');
        $qb = $aclNativeHelper->apply($qb, $permissionDef);

        $stmt = $this->_em->getConnection()->prepare($qb->getSQL());
        $stmt->bindValue(':lang', $lang);
        if (null !== $rootNode) {
            $stmt->bindValue(':left', $rootNode->getLeft());
            $stmt->bindValue(':right', $rootNode->getRight());
        }
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    /**
     * Get all parents of a given node. We can go multiple levels up.
     *
     * @param Node   $node
     * @param string $lang
     *
     * @return Node[]
     */
    public function getAllParents(?Node $node = null, $lang = null)
    {
        if (null === $node) {
            return [];
        }

        $qb = $this->createQueryBuilder('node');

        // Directly hydrate the nodeTranslation and nodeVersion
        $qb->select('node', 't', 'v')
            ->innerJoin('node.nodeTranslations', 't')
            ->leftJoin(
                't.publicNodeVersion',
                'v',
                'WITH',
                't.publicNodeVersion = v.id'
            )
            ->where('node.deleted = 0');

        if ($lang) {
            $qb->andWhere('t.lang = :lang')
                ->setParameter('lang', $lang);
        }

        $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->lte('node.lft', $node->getLeft()),
                $qb->expr()->gte('node.rgt', $node->getRight())
            )
        );

        $qb->addOrderBy('node.lft', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the root node of a given node.
     *
     * @param Node   $node
     * @param string $lang
     *
     * @return Node
     */
    public function getRootNodeFor(?Node $node = null, $lang = null)
    {
        if (null === $node) {
            return null;
        }

        $qb = $this->createQueryBuilder('node');

        // Directly hydrate the nodeTranslation and nodeVersion
        $qb->select('node', 't', 'v')
            ->innerJoin('node.nodeTranslations', 't')
            ->leftJoin(
                't.publicNodeVersion',
                'v',
                'WITH',
                't.publicNodeVersion = v.id'
            )
            ->where('node.deleted = 0')
            ->andWhere('node.parent IS NULL');

        if ($lang) {
            $qb->andWhere('t.lang = :lang')
                ->setParameter('lang', $lang);
        }

        $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->lte('node.lft', $node->getLeft()),
                $qb->expr()->gte('node.rgt', $node->getRight())
            )
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Node[]
     */
    public function getAllTopNodes()
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b', 't', 'v')
            ->leftJoin('b.nodeTranslations', 't')
            ->leftJoin(
                't.publicNodeVersion',
                'v',
                'WITH',
                't.publicNodeVersion = v.id'
            )
            ->where('b.deleted = 0')
            ->andWhere('b.parent IS NULL');

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get an array of Nodes based on the internal name.
     *
     * @param string        $internalName   The internal name of the node
     * @param string        $lang           The locale
     * @param null|bool|int $parentId       The parent id
     * @param bool          $includeOffline Include offline nodes
     *
     * @return Node[]
     */
    public function getNodesByInternalName(
        $internalName,
        $lang,
        $parentId = false,
        $includeOffline = false
    ) {
        $qb = $this->createQueryBuilder('n')
            ->select('n', 't', 'v')
            ->innerJoin('n.nodeTranslations', 't')
            ->leftJoin(
                't.publicNodeVersion',
                'v',
                'WITH',
                't.publicNodeVersion = v.id'
            )
            ->where('n.deleted = 0')
            ->andWhere('n.internalName = :internalName')
            ->setParameter('internalName', $internalName)
            ->andWhere('t.lang = :lang')
            ->setParameter('lang', $lang)
            ->addOrderBy('t.weight', 'ASC')
            ->addOrderBy('t.title', 'ASC');

        if (!$includeOffline) {
            $qb->andWhere('t.online = true');
        }

        if (null === $parentId) {
            $qb->andWhere('n.parent is NULL');
        } elseif (false === $parentId) {
            // Do nothing
        } else {
            $qb->andWhere('n.parent = :parent')
                ->setParameter('parent', $parentId);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Get a single node by internal name.
     *
     * @param string $internalName The internal name of the node
     *
     * @return Node
     */
    public function getNodeByInternalName($internalName)
    {
        $qb = $this->createQueryBuilder('n')
            ->select('n')
            ->where('n.deleted = 0')
            ->andWhere('n.internalName = :internalName')
            ->setParameter('internalName', $internalName);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds all different page classes currently registered as nodes.
     *
     * @return string[]
     */
    public function findAllDistinctPageClasses()
    {
        $qb = $this->createQueryBuilder('n')
            ->select('n.refEntityName')
            ->distinct(true);

        return $qb->getQuery()->getArrayResult();
    }
}
