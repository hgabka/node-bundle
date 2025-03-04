<?php

namespace Hgabka\NodeBundle\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\UtilsBundle\Helper\ClassLookup;

/**
 * NodeRepository.
 */
class NodeVersionRepository extends EntityRepository
{
    /**
     * @return NodeVersion
     */
    public function getNodeVersionFor(HasNodeInterface $hasNode)
    {
        return $this->findOneBy(
            [
                'refId' => $hasNode->getId(),
                'refEntityName' => ClassLookup::getClass($hasNode),
            ]
        );
    }

    /**
     * @param HasNodeInterface $hasNode         The object
     * @param NodeTranslation  $nodeTranslation The node translation
     * @param BaseUser         $owner           The user
     * @param NodeVersion      $origin          The nodeVersion this nodeVersion originated from
     * @param string           $type            (public|draft)
     * @param DateTime         $created         The date this node version is created
     *
     * @return NodeVersion
     */
    public function createNodeVersionFor(
        HasNodeInterface $hasNode,
        NodeTranslation $nodeTranslation,
        $owner,
        ?NodeVersion $origin = null,
        $type = 'public',
        $created = null
    ) {
        $em = $this->getEntityManager();

        $nodeVersion = new NodeVersion();
        $nodeVersion->setNodeTranslation($nodeTranslation);
        $nodeVersion->setType($type);
        $nodeVersion->setOwner($owner);
        $nodeVersion->setRef($hasNode);
        $nodeVersion->setOrigin($origin);

        if (null !== $created) {
            $nodeVersion->setCreated($created);
        }

        $em->persist($nodeVersion);
        $em->flush();
        $em->refresh($nodeVersion);

        return $nodeVersion;
    }
}
