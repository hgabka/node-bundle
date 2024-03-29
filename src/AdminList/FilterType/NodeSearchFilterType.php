<?php

namespace Hgabka\NodeBundle\AdminList\FilterType;

use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Search\NodeSearcher;
use Hgabka\UtilsBundle\AdminList\FilterType\ORM\AbstractORMFilterType;
use Symfony\Component\HttpFoundation\Request;

class NodeSearchFilterType extends AbstractORMFilterType
{
    /** @var NodeSearcher */
    protected $nodeSearcher;

    /**
     * @param string $columnName The column name
     * @param string $alias      The alias
     */
    public function __construct(NodeSearcher $searcher, $alias = 'b')
    {
        $this->nodeSearcher = $searcher;
        $this->alias = $alias;
    }

    public function bindRequest(Request $request, array &$data, $uniqueId)
    {
        $data['comparator'] = $request->query->get('filter_comparator_' . $uniqueId);
        $data['value'] = $request->query->get('filter_value_' . $uniqueId);
    }

    /**
     * @param array  $data     The data
     * @param string $uniqueId The unique identifier
     */
    public function apply(array $data, $uniqueId)
    {
        if (empty($data['value'])) {
            return;
        }
        $res = $this->nodeSearcher->search(search: $data['value'], lang: false);
        $comparator = $data['comparator'] ?? 'contains';

        if (!empty($res)) {
            $ids = [];
            foreach ($res as $row) {
                /** @var NodeTranslation $nt */
                $nt = $row['nodeTranslation'] ?? null;
                if ($nt instanceof NodeTranslation) {
                    $ids[] = $nt->getNode()->getId();
                }
            }

            if (!empty($ids)) {
                $ids = array_unique($ids);

                if ('contains' === $comparator) {
                    $this->queryBuilder->andWhere('n.id IN (:ids_' . $uniqueId . ')')->setParameter('ids_' . $uniqueId, $ids);
                } else {
                    $this->queryBuilder->andWhere('n.id NOT IN (:ids_' . $uniqueId . ')')->setParameter('ids_' . $uniqueId, $ids);
                }
            } else {
                if ('contains' === $comparator) {
                    $this->queryBuilder->andWhere('1=0');
                }
            }
        } else {
            if ('contains' === $comparator) {
                $this->queryBuilder->andWhere('1=0');
            }
        }
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return '@HgabkaNode/FilterType/nodeSearchFilter.html.twig';
    }
}
