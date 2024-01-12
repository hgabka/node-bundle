<?php

namespace Hgabka\NodeBundle\Helper\NodeAdmin;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersionLock;
use Hgabka\NodeBundle\Repository\NodeVersionLockRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NodeVersionLockHelper
{
    public function __construct(private readonly ParameterBagInterface $params, private readonly EntityManagerInterface $objectManager)
    {
    }

    /**
     * @param bool $isPublicNodeVersion
     *
     * @return bool
     */
    public function isNodeVersionLocked(BaseUser $user, NodeTranslation $nodeTranslation, $isPublicNodeVersion)
    {
        if ($this->params->get('hg_node.lock_enabled')) {
            $this->removeExpiredLocks($nodeTranslation);
            $this->createNodeVersionLock($user, $nodeTranslation, $isPublicNodeVersion); // refresh lock
            $locks = $this->getNodeVersionLocksByNodeTranslation($nodeTranslation, $isPublicNodeVersion, $user);

            return \count($locks) ? true : false;
        }

        return false;
    }

    /**
     * @param BaseUser $userToExclude
     * @param bool     $isPublicNodeVersion
     *
     * @return array
     */
    public function getUsersWithNodeVersionLock(NodeTranslation $nodeTranslation, $isPublicNodeVersion, BaseUser $userToExclude = null)
    {
        return array_reduce(
            $this->getNodeVersionLocksByNodeTranslation($nodeTranslation, $isPublicNodeVersion, $userToExclude),
            function ($return, NodeVersionLock $item) {
                $return[] = $item->getOwner();

                return $return;
            },
            []
        );
    }

    protected function removeExpiredLocks(NodeTranslation $nodeTranslation)
    {
        $threshold = $this->params->get('hg_node.lock_threshold');
        $locks = $this->objectManager->getRepository(NodeVersionLock::class)->getExpiredLocks($nodeTranslation, $threshold);
        foreach ($locks as $lock) {
            $this->objectManager->remove($lock);
        }
    }

    /**
     * When editing the node, create a new node translation lock.
     *
     * @param bool $isPublicVersion
     */
    protected function createNodeVersionLock(BaseUser $user, NodeTranslation $nodeTranslation, $isPublicVersion)
    {
        $lock = $this->objectManager->getRepository(NodeVersionLock::class)->findOneBy([
            'owner' => $user->getUserIdentifier(),
            'nodeTranslation' => $nodeTranslation,
            'publicVersion' => $isPublicVersion,
        ]);
        if (!$lock) {
            $lock = new NodeVersionLock();
        }
        $lock->setOwner($user->getUserIdentifier());
        $lock->setNodeTranslation($nodeTranslation);
        $lock->setPublicVersion($isPublicVersion);
        $lock->setCreatedAt(new \DateTime());

        $this->objectManager->persist($lock);
        $this->objectManager->flush();
    }

    /**
     * When editing a node, check if there is a lock for this node translation.
     *
     * @param bool     $isPublicVersion
     * @param BaseUser $userToExclude
     *
     * @return NodeVersionLock[]
     */
    protected function getNodeVersionLocksByNodeTranslation(NodeTranslation $nodeTranslation, $isPublicVersion, BaseUser $userToExclude = null)
    {
        $threshold = $this->params->get('hg_node.lock_threshold');
        /** @var NodeVersionLockRepository $objectRepository */
        $objectRepository = $this->objectManager->getRepository(NodeVersionLock::class);

        return $objectRepository->getLocksForNodeTranslation($nodeTranslation, $isPublicVersion, $threshold, $userToExclude);
    }
}
