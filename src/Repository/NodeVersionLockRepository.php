<?php

namespace Hgabka\NodeBundle\Repository;

use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersionLock;

/**
 * NodeVersionLockRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NodeVersionLockRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Check if there is a nodetranslation lock that's not passed the 30 minute threshold.
     *
     * @param NodeTranslation $nodeTranslation
     * @param bool            $isPublicVersion
     * @param int             $threshold
     * @param object          $userToExclude
     *
     * @return NodeVersionLock[]
     */
    public function getLocksForNodeTranslation(NodeTranslation $nodeTranslation, $isPublicVersion, $threshold, $userToExclude = null)
    {
        $qb = $this->createQueryBuilder('nvl')
            ->select('nvl')
            ->where('nvl.nodeTranslation = :nt')
            ->andWhere('nvl.publicVersion = :pub')
            ->andWhere('nvl.createdAt > :date')
            ->setParameter('nt', $nodeTranslation)
            ->setParameter('pub', $isPublicVersion)
            ->setParameter('date', new \DateTime(sprintf('-%s seconds', $threshold)))
        ;

        if (null !== $userToExclude && method_exists($userToExclude, 'getUsername')) {
            $qb->andWhere('nvl.owner <> :owner')
                ->setParameter('owner', $userToExclude->getUsername())
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get locks that are passed the threshold.
     *
     * @param NodeTranslation $nodeTranslation
     * @param int             $threshold
     *
     * @return mixed
     */
    public function getExpiredLocks(NodeTranslation $nodeTranslation, $threshold)
    {
        $qb = $this->createQueryBuilder('nvl')
            ->select('nvl')
            ->where('nvl.nodeTranslation = :nt')
            ->andWhere('nvl.createdAt < :date')
            ->setParameter('nt', $nodeTranslation)
            ->setParameter('date', new \DateTime(sprintf('-%s seconds', $threshold)));

        return $qb->getQuery()->getResult();
    }
}
