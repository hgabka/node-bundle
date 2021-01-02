<?php

namespace Hgabka\NodeBundle\Search;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\PagePartBundle\Entity\PagePartRef;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\PropertyAccess\PropertyAccess;

class NodeSearcher
{
    /** @var EntityManagerInterface */
    protected $doctrine;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /**
     * NodeSearcher constructor.
     */
    public function __construct(EntityManagerInterface $doctrine, HgabkaUtils $hgabkaUtils)
    {
        $this->doctrine = $doctrine;
        $this->hgabkaUtils = $hgabkaUtils;
    }

    /**
     * Keres a megadott oldaltípusok és oldalelemek megadott mezőiben.
     *
     * Pl:
     * $nodeSearcher->search(
     * 'keresett szöveg'
     * [ ContentPage::class => ['title', 'lead']],
     * [ TextPagePart::class => ['content'],
     * $rootNode,
     * 'hu'
     * );
     *
     * @param string $search          - a keresett szöveg
     * @param array  $pageClasses     - a keresendő oldalosztályok és a mezők, amelyben a keresés keressen
     * @param array  $pagepartClasses - a keresendő oldalelem típusok  és a mezők, amelyben a keresés keressen
     * @param null   $rootNode        - csak ezen node alatt keressen, ha null, akkor minden node jöhet
     * @param null   $lang            - csak ezen nyelvű változatokban keres, ha null, akkor az aktuális nyelv lesz, ha más üres érték, akkor nincs nyelvi megkötés
     */
    public function search(string $search, array $pageClasses, array $pagepartClasses, $rootNode = null, $lang = null)
    {
        if (null === $lang) {
            $lang = $this->hgabkaUtils->getCurrentLocale();
        }

        /** @var QueryBuilder $qb */
        $qb =
            $this->doctrine->getRepository(NodeTranslation::class)
             ->createQueryBuilder('nt')
             ->select('nt')
             ->innerJoin('nt.node', 'n')
             ->innerJoin(
                 'nt.publicNodeVersion',
                 'v',
                 'WITH',
                 'nt.publicNodeVersion = v.id'
             )
             ->where('n.deleted = false')
             ->andWhere('nt.online = true')
             ->orderBy('nt.weight')
             ->addOrderBy('nt.weight');

        if (!empty($lang)) {
            $qb
                ->andWhere('nt.lang = :lang')
                ->setParameter('lang', $lang);
        }

        if (!empty($rootNode)) {
            $qb->andWhere('n.lft >= :left')
               ->andWhere('n.rgt <= :right')
               ->setParameter('left', $rootNode->getLeft())
               ->setParameter('right', $rootNode->getRight());
        }

        if (!empty($pageClasses)) {
            $qb
                ->andWhere($qb->expr()->in('v.refEntityName', array_keys($pageClasses)))
            ;
        }

        $qb->leftJoin(PagePartRef::class, 'pp', 'WITH', 'pp.pageEntityname = v.refEntityName AND pp.pageId = v.refId');

        $key = 0;
        $orX = $qb->expr()->orX();
        foreach ($pagepartClasses as $pagepartClass => $fields) {
            if (empty($fields)) {
                continue;
            }
            $qb
                ->leftJoin($pagepartClass, 'ppc'.$key, 'WITH', 'pp.pagePartId = ppc'.$key.'.id AND pp.pagePartEntityname = :pagepartclass'.$key)
                ->setParameter('pagepartclass'.$key, $pagepartClass)
            ;
            $qb->addSelect('ppc'.$key);
            if (\is_string($fields)) {
                $fields = [$fields];
            }

            foreach ($fields as $field) {
                $orX->add($qb->expr()->like('ppc'.$key.'.'.$field, ':search'));
            }
            ++$key;
        }

        $key = 0;
        foreach ($pageClasses as $pageClass => $fields) {
            if (empty($fields)) {
                continue;
            }
            $qb
                ->leftJoin($pageClass, 'pc'.$key, 'WITH', 'v.refId = pc'.$key.'.id AND v.refEntityName = :pageclass'.$key)
                ->setParameter('pageclass'.$key, $pageClass)
            ;
            $qb->addSelect('pc'.$key);
            if (\is_string($fields)) {
                $fields = [$fields];
            }

            foreach ($fields as $field) {
                $orX->add($qb->expr()->like('pc'.$key.'.'.$field, ':search'));
            }
            ++$key;
        }

        if ($orX->count() > 0) {
            $qb->andWhere($orX)->setParameter('search', '%'.addcslashes($search, '%_').'%');
        }

        $qb->groupBy('nt.id');

        $results = $qb->getQuery()->getResult();
        $row = -1;
        $ret = [];
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($results as $result) {
            if (empty($result)) {
                continue;
            }

            if ($result instanceof NodeTranslation) {
                ++$row;
                $ret[$row]['nodeTranslation'] = $result;
                $ret[$row]['texts'] = [];
            }
            if (\in_array(\get_class($result), array_keys($pagepartClasses), true)) {
                if (empty($ret[$row]['pageParts'])) {
                    $ret[$row]['pageParts'][] = $result;
                }

                $fields = $pagepartClasses[\get_class($result)];
                if (!empty($fields)) {
                    if (\is_string($fields)) {
                        $fields = [$fields];
                    }

                    foreach ($fields as $field) {
                        if (!empty($field)) {
                            $text = $propertyAccessor->getValue($result, $field);
                            if (false !== mb_strpos($text, $search)) {
                                $ret[$row]['texts'][] = [
                                    'class' => \get_class($result),
                                    'id' => $result->getId(),
                                    'field' => $field,
                                    'text' => $text,
                                ];
                            }
                        }
                    }
                }
            }
            if (\in_array(\get_class($result), array_keys($pageClasses), true)) {
                $ret[$row]['page'] = $result;
                $fields = $pageClasses[\get_class($result)];
                if (!empty($fields)) {
                    if (\is_string($fields)) {
                        $fields = [$fields];
                    }

                    foreach ($fields as $field) {
                        if (!empty($field)) {
                            $text = $propertyAccessor->getValue($result, $field);
                            if (false !== mb_strpos($text, $search)) {
                                $ret[$row]['texts'][] = [
                                    'class' => \get_class($result),
                                    'id' => $result->getId(),
                                    'field' => $field,
                                    'text' => $text,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $ret;
    }
}
