<?php

namespace Hgabka\NodeBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\UtilsBundle\Helper\ClassLookup;

/**
 * NodeRepository.
 */
class NodeTranslationRepository extends EntityRepository
{
    protected $nodeTranslationsByNode = [];
    protected $nodeTranslationsByLanguageAndInternalName = [];
    protected $nodeTranslationsByRefEntityName = [];

    /**
     * Get the QueryBuilder based on node id and language.
     *
     * @param int    $nodeId
     * @param string $lang
     *
     * @return array_shift($result)
     */
    public function getNodeTranslationByNodeIdQueryBuilder($nodeId, $lang)
    {
        if (!array_key_exists($nodeId, $this->nodeTranslationsByNode) || !array_key_exists($lang, $this->nodeTranslationsByNode[$nodeId])) {
            $qb = $this->createQueryBuilder('nt')
                       ->select('nt')
                       ->innerJoin('nt.node', 'n', 'WITH', 'nt.node = n.id')
                       ->where('n.deleted != 1')
                       ->andWhere('nt.online = 1')
                       ->andWhere('nt.lang = :lang')
                       ->setParameter('lang', $lang)
                       ->andWhere('n.id = :node_id')
                       ->setParameter('node_id', $nodeId)
                       ->setFirstResult(0)
                       ->setMaxResults(1);

            $this->nodeTranslationsByNode[$nodeId][$lang] = $qb->getQuery()->getOneOrNullResult();
        }

        return $this->nodeTranslationsByNode[$nodeId][$lang];
    }

    /**
     * Get max children weight.
     *
     * @param Node   $parentNode
     * @param string $lang       (optional) Only return max weight for the
     *                           given language
     *
     * @return int
     */
    public function getMaxChildrenWeight(?Node $parentNode = null, ?string $lang = null)
    {
        $maxWeight = $this->getNodeTranslationsQueryBuilder($lang)
            ->select('max(nt.weight)')
            ->andWhere('n.parent = :parentNode')
            ->setParameter('parentNode', $parentNode)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $maxWeight;
    }

    /**
     * QueryBuilder to fetch node translations (ignoring nodes that have been
     * deleted).
     *
     * @param string $lang (optional) Only return NodeTranslations for the
     *                     given language
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getNodeTranslationsQueryBuilder(?string $lang = null)
    {
        $queryBuilder = $this->createQueryBuilder('nt')
            ->select('nt,n,v')
            ->innerJoin('nt.node', 'n')
            ->leftJoin(
                'nt.publicNodeVersion',
                'v',
                'WITH',
                'nt.publicNodeVersion = v.id'
            )
            ->where('n.deleted = false')
            ->orderBy('nt.weight')
            ->addOrderBy('nt.weight');

        if (!empty($lang)) {
            $queryBuilder
                ->andWhere('nt.lang = :lang')
                ->setParameter('lang', $lang);
        }

        return $queryBuilder;
    }

    /**
     * QueryBuilder to fetch node translations that are currently published
     * (ignoring nodes that have been deleted).
     *
     * @param string $lang (optional) Only return NodeTranslations for the
     *                     given language
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOnlineNodeTranslationsQueryBuilder(?string $lang = null)
    {
        return $this->getNodeTranslationsQueryBuilder($lang)
            ->andWhere('nt.online = true');
    }

    /**
     * QueryBuilder to fetch immediate child NodeTranslations for a specific
     * node and (optional) language.
     *
     * @param null|mixed $lang
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getChildrenQueryBuilder(Node $parent, ?string $lang = null)
    {
        return $this->getNodeTranslationsQueryBuilder($lang)
            ->andWhere('n.parent = :parent')
            ->setParameter('parent', $parent);
    }

    /**
     * QueryBuilder to fetch immediate child NodeTranslations for a specific
     * node and (optional) language that are currently published.
     *
     * @param null|mixed $lang
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOnlineChildrenQueryBuilder(Node $parent, ?string $lang = null)
    {
        return $this->getChildrenQueryBuilder($parent, $lang)
            ->andWhere('nt.online = true');
    }

    /**
     * Get all online child node translations for a given node and (optional)
     * language.
     *
     * @param string $lang (optional, if not specified all languages will be
     *                     returned)
     *
     * @return array
     */
    public function getOnlineChildren(Node $parent, ?string $lang = null)
    {
        return $this->getOnlineChildrenQueryBuilder($parent, $lang)
            ->getQuery()->getResult();
    }

    /**
     * Get the node translation for a node.
     *
     * @return NodeTranslation
     */
    public function getNodeTranslationFor(HasNodeInterface $hasNode)
    {
        // @var NodeVersion $nodeVersion
        $nodeVersion = $this->getEntityManager()
            ->getRepository(NodeVersion::class)
            ->getNodeVersionFor($hasNode);

        if (null !== $nodeVersion) {
            return $nodeVersion->getNodeTranslation();
        }

        return null;
    }

    /**
     * Get the node translation for a given slug string.
     *
     * @param string               $slug       The slug
     * @param null|NodeTranslation $parentNode The parentnode
     *
     * @return null|NodeTranslation
     */
    public function getNodeTranslationForSlug(
        $slug,
        ?NodeTranslation $parentNode = null
    ) {
        if (empty($slug)) {
            return $this->getNodeTranslationForSlugPart(null, $slug);
        }

        $slugParts = explode('/', $slug);
        $result = $parentNode;
        foreach ($slugParts as $slugPart) {
            $result = $this->getNodeTranslationForSlugPart($result, $slugPart);
        }

        return $result;
    }

    /**
     * Get the node translation for a given url.
     *
     * @param string          $urlSlug        The full url
     * @param string          $locale         The locale
     * @param bool            $includeDeleted Include deleted nodes
     * @param NodeTranslation $toExclude      Optional NodeTranslation instance
     *                                        you wish to exclude
     * @param Node            $rootNode       Optional Root node of the tree you
     *                                        wish to use
     *
     * @return array
     */
    public function getAllNodeTranslationsForUrl(
        $urlSlug,
        $locale = '',
        $includeDeleted = false,
        ?NodeTranslation $toExclude = null,
        ?Node $rootNode = null
    ) {
        $qb = $this->createQueryBuilder('b')
            ->select('b', 'v')
            ->innerJoin('b.node', 'n', 'WITH', 'b.node = n.id')
            ->leftJoin(
                'b.publicNodeVersion',
                'v',
                'WITH',
                'b.publicNodeVersion = v.id'
            )
            ->addOrderBy('b.online', 'DESC')
            ->setFirstResult(0)
        //    ->setMaxResults(1)
        ;

        if (!$includeDeleted) {
            $qb->andWhere('n.deleted = 0');
        }

        if (!empty($locale)) {
            $qb->andWhere('b.lang = :lang')
                ->setParameter('lang', $locale);
        }

        if (empty($urlSlug)) {
            $qb->andWhere('b.url IS NULL');
        } else {
            $qb->andWhere('b.url = :url');
            $qb->setParameter('url', $urlSlug);
        }

        if (null !== $toExclude) {
            $qb->andWhere('NOT b.id = :exclude_id')
                ->setParameter('exclude_id', $toExclude->getId());
        }

        if ($rootNode) {
            $qb->andWhere('n.lft >= :left')
                ->andWhere('n.rgt <= :right')
                ->setParameter('left', $rootNode->getLeft())
                ->setParameter('right', $rootNode->getRight());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the node translation for a given url.
     *
     * @param string          $urlSlug        The full url
     * @param string          $locale         The locale
     * @param bool            $includeDeleted Include deleted nodes
     * @param NodeTranslation $toExclude      Optional NodeTranslation instance
     *                                        you wish to exclude
     * @param Node            $rootNode       Optional Root node of the tree you
     *                                        wish to use
     *
     * @return null|NodeTranslation
     */
    public function getNodeTranslationForUrl(
        $urlSlug,
        $locale = '',
        $includeDeleted = false,
        ?NodeTranslation $toExclude = null,
        ?Node $rootNode = null
    ) {
        $translations = $this->getAllNodeTranslationsForUrl($urlSlug, $locale, $includeDeleted, $toExclude, $rootNode);

        if (empty($translations)) {
            return null;
        }

        return $translations[0];
    }

    /**
     * Get all top node translations.
     *
     * @return NodeTranslation[]
     */
    public function getTopNodeTranslations()
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b', 'v')
            ->innerJoin('b.node', 'n', 'WITH', 'b.node = n.id')
            ->leftJoin(
                'b.publicNodeVersion',
                'v',
                'WITH',
                'b.publicNodeVersion = v.id'
            )
            ->where('n.parent IS NULL')
            ->andWhere('n.deleted != 1');

        return $qb->getQuery()->getResult();
    }

    /**
     * Create a node translation for a given node.
     *
     * @param HasNodeInterface $hasNode The hasNode
     * @param string           $lang    The locale
     * @param Node             $node    The node
     * @param object           $owner   The user
     *
     * @throws \InvalidArgumentException
     *
     * @return NodeTranslation
     */
    public function createNodeTranslationFor(
        HasNodeInterface $hasNode,
        $lang,
        Node $node,
        $owner
    ) {
        $em = $this->getEntityManager();
        $className = ClassLookup::getClass($hasNode);
        if (!$hasNode->getId() > 0) {
            throw new \InvalidArgumentException('The entity of class ' . $className . ' has no id, maybe you forgot to flush first');
        }

        $nodeTranslation = new NodeTranslation();
        $nodeTranslation
            ->setNode($node)
            ->setLang($lang)
            ->setTitle($hasNode->getTitle())
            ->setOnline(false)
            ->setWeight(0);

        $em->persist($nodeTranslation);

        $nodeVersion = $em->getRepository(NodeVersion::class)
            ->createNodeVersionFor(
                $hasNode,
                $nodeTranslation,
                $owner,
                null
            );

        $nodeTranslation->setPublicNodeVersion($nodeVersion);
        $em->persist($nodeTranslation);
        $em->flush();
        $em->refresh($nodeTranslation);
        $em->refresh($node);

        return $nodeTranslation;
    }

    /**
     * Add a draft node version for a given node.
     *
     * @param HasNodeInterface $hasNode The hasNode
     * @param string           $lang    The locale
     * @param Node             $node    The node
     * @param object           $owner   The user
     *
     * @throws \InvalidArgumentException
     *
     * @return NodeTranslation
     */
    public function addDraftNodeVersionFor(
        HasNodeInterface $hasNode,
        $lang,
        Node $node,
        $owner
    ) {
        $em = $this->getEntityManager();
        $className = ClassLookup::getClass($hasNode);
        if (!$hasNode->getId() > 0) {
            throw new \InvalidArgumentException('The entity of class ' . $className . ' has no id, maybe you forgot to flush first');
        }

        $nodeTranslation = $em->getRepository(NodeTranslation::class)->findOneBy(['lang' => $lang, 'node' => $node]);

        $em->getRepository(NodeVersion::class)
            ->createNodeVersionFor(
                $hasNode,
                $nodeTranslation,
                $owner,
                null,
                NodeVersion::DRAFT_VERSION
            );

        $em->refresh($nodeTranslation);
        $em->refresh($node);

        return $nodeTranslation;
    }

    /**
     * Find best match for given URL and locale.
     *
     * @param string $urlSlug The slug
     * @param string $locale  The locale
     *
     * @return NodeTranslation
     */
    public function getBestMatchForUrl($urlSlug, $locale)
    {
        $em = $this->getEntityManager();

        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(
            NodeTranslation::class,
            'nt'
        );

        $query = $em
            ->createNativeQuery(
                'select nt.*
                from hg_node_node_translations nt
                join hg_node_nodes n on n.id = nt.node_id
                where n.deleted = 0 and nt.lang = :lang and locate(nt.url, :url) = 1
                order by length(nt.url) desc limit 1',
                $rsm
            );
        $query->setParameter('lang', $locale);
        $query->setParameter('url', $urlSlug);
        $translation = $query->getOneOrNullResult();

        return $translation;
    }

    /**
     * Test if all parents of the specified NodeTranslation have a node
     * translation for the specified language.
     *
     * @param NodeTranslation $nodeTranslation The node translation
     * @param string          $language        The locale
     *
     * @return bool
     */
    public function hasParentNodeTranslationsForLanguage(
        NodeTranslation $nodeTranslation,
        $language
    ) {
        $parentNode = $nodeTranslation->getNode()->getParent();
        if (null !== $parentNode) {
            $parentNodeTranslation = $parentNode->getNodeTranslation(
                $language,
                true
            );
            if (null !== $parentNodeTranslation) {
                return $this->hasParentNodeTranslationsForLanguage(
                    $parentNodeTranslation,
                    $language
                );
            }

            return false;
        }

        return true;
    }

    /**
     * This will return 1 NodeTranslation by default (if one exists).
     * Just give it the internal name as defined on the Node in the database
     * and the language.
     *
     * It'll only return the latest version. It'll also hide deleted & offline
     * nodes.
     *
     * @param $language
     * @param $internalName
     */
    public function getNodeTranslationByLanguageAndInternalName(
        $language,
        $internalName
    ) {
        if (!array_key_exists($internalName, $this->nodeTranslationsByLanguageAndInternalName)
            || !array_key_exists($lang, $this->nodeTranslationsByLanguageAndInternalName[$internalName])
        ) {
            $qb = $this->createQueryBuilder('nt')
                       ->select('nt', 'v')
                       ->innerJoin('nt.node', 'n', 'WITH', 'nt.node = n.id')
                       ->leftJoin(
                           'nt.publicNodeVersion',
                           'v',
                           'WITH',
                           'nt.publicNodeVersion = v.id'
                       )
                       ->where('n.deleted != 1')
                       ->andWhere('nt.online = 1')
                       ->setFirstResult(0)
                       ->setMaxResults(1);

            $qb->andWhere('nt.lang = :lang')
               ->setParameter('lang', $language)
            ;

            $qb->andWhere('n.internalName = :internal_name')
               ->setParameter('internal_name', $internalName)
            ;

            $this->nodeTranslationsByLanguageAndInternalName[$internalName][$lang] = $qb->getQuery()->getOneOrNullResult();
        }

        return $this->nodeTranslationsByLanguageAndInternalName[$internalName][$lang];
    }

    public function getAllNodeTranslationsByRefEntityName($refEntityName)
    {
        if (!array_key_exists($refEntityName, $this->nodeTranslationsByRefEntityName)) {
            $qb = $this->createQueryBuilder('nt')
                       ->select('nt,n')
                       ->innerJoin('nt.publicNodeVersion', 'nv')
                       ->innerJoin('nt.node', 'n')
                       ->where('nv.refEntityName = :refEntityName')
                       ->setParameter('refEntityName', $refEntityName);

            $this->nodeTranslationsByRefEntityName[$refEntityName] = $qb->getQuery()->getResult();
        }

        return $this->nodeTranslationsByRefEntityName[$refEntityName];
    }

    public function getParentNodeTranslation(NodeTranslation $nodeTranslation)
    {
        $parent = $nodeTranslation->getNode()->getParent();
        if (null === $parent) {
            return null;
        }

        $qb = $this->createQueryBuilder('nt')
            ->select('nt,n')
            ->innerJoin('nt.publicNodeVersion', 'nv')
            ->innerJoin('nt.node', 'n')
            ->where('nt.node = :parent')
            ->andWhere('n.deleted = 0')
            ->andWhere('nt.lang = :lang')
            ->setParameter('parent', $parent)
            ->setParameter('lang', $nodeTranslation->getLang());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns the node translation for a given slug.
     *
     * @param null|NodeTranslation $parentNode The parentNode
     * @param string               $slugPart   The slug part
     *
     * @return null|NodeTranslation
     */
    private function getNodeTranslationForSlugPart(
        ?NodeTranslation $parentNode = null,
        $slugPart = ''
    ) {
        $qb = $this->createQueryBuilder('t')
            ->select('t', 'v', 'n')
            ->innerJoin('t.node', 'n', 'WITH', 't.node = n.id')
            ->leftJoin(
                't.publicNodeVersion',
                'v',
                'WITH',
                't.publicNodeVersion = v.id'
            )
            ->where('n.deleted != 1')
            ->setFirstResult(0)
            ->setMaxResults(1);

        if (null !== $parentNode) {
            $qb->andWhere('t.slug = :slug')
                ->andWhere('n.parent = :parent')
                ->setParameter('slug', $slugPart)
                ->setParameter('parent', $parentNode->getNode()->getId());
        } else {
            // if parent is null we should look for slugs that have no parent
            $qb->andWhere('n.parent IS NULL');
            if (empty($slugPart)) {
                $qb->andWhere('t.slug is NULL');
            } else {
                $qb->andWhere('t.slug = :slug');
                $qb->setParameter('slug', $slugPart);
            }
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
