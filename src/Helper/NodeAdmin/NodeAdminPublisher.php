<?php

namespace Hgabka\NodeBundle\Helper\NodeAdmin;

use Doctrine\ORM\EntityManager;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Entity\QueuedNodeTranslationAction;
use Hgabka\NodeBundle\Event\Events;
use Hgabka\NodeBundle\Event\NodeEvent;
use Hgabka\UtilsBundle\Helper\CloneHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class NodeAdminPublisher
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CloneHelper
     */
    private $cloneHelper;

    /**
     * @param EntityManager                 $em                   The entity manager
     * @param TokenStorageInterface         $tokenStorage         The security token storage
     * @param AuthorizationCheckerInterface $authorizationChecker The security authorization checker
     * @param EventDispatcherInterface      $eventDispatcher      The Event dispatcher
     * @param CloneHelper                   $cloneHelper          The clone helper
     */
    public function __construct(
        EntityManager $em,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        EventDispatcherInterface $eventDispatcher,
        CloneHelper $cloneHelper
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->eventDispatcher = $eventDispatcher;
        $this->cloneHelper = $cloneHelper;
    }

    /**
     * If there is a draft version it'll try to publish the draft first. Makse snese because if you want to publish the public version you don't publish but you save.
     *
     * @param null|mixed $user
     *
     * @throws AccessDeniedException
     */
    public function publish(NodeTranslation $nodeTranslation, $user = null)
    {
        if (false === $this->authorizationChecker->isGranted(PermissionMap::PERMISSION_PUBLISH, $nodeTranslation->getNode())) {
            throw new AccessDeniedException();
        }

        if (null === $user) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        $node = $nodeTranslation->getNode();

        $nodeVersion = $nodeTranslation->getNodeVersion('draft');
        if (null !== $nodeVersion) {
            $page = $nodeVersion->getRef($this->em);
            /** @var $nodeVersion NodeVersion */
            $nodeVersion = $this->createPublicVersion($page, $nodeTranslation, $nodeVersion, $user);
            $nodeTranslation = $nodeVersion->getNodeTranslation();
        } else {
            $nodeVersion = $nodeTranslation->getPublicNodeVersion();
        }

        $page = $nodeVersion->getRef($this->em);

        $this->eventDispatcher->dispatch(
            new NodeEvent($node, $nodeTranslation, $nodeVersion, $page),
            Events::PRE_PUBLISH
        );
        $nodeTranslation
            ->setOnline(true)
            ->setPublicNodeVersion($nodeVersion)
            ->setUpdated(new \DateTime());
        $this->em->persist($nodeTranslation);
        $this->em->flush();

        // Remove scheduled task
        $this->unSchedulePublish($nodeTranslation);

        $this->eventDispatcher->dispatch(
            new NodeEvent($node, $nodeTranslation, $nodeVersion, $page),
            Events::POST_PUBLISH
        );
    }

    /**
     * @param NodeTranslation $nodeTranslation The NodeTranslation
     * @param \DateTime       $date            The date to publish
     *
     * @throws AccessDeniedException
     */
    public function publishLater(NodeTranslation $nodeTranslation, \DateTime $date)
    {
        $node = $nodeTranslation->getNode();
        if (false === $this->authorizationChecker->isGranted(PermissionMap::PERMISSION_PUBLISH, $node)) {
            throw new AccessDeniedException();
        }

        // remove existing first
        $this->unSchedulePublish($nodeTranslation);

        $user = $this->tokenStorage->getToken()->getUser();
        $queuedNodeTranslationAction = new QueuedNodeTranslationAction();
        $queuedNodeTranslationAction
            ->setNodeTranslation($nodeTranslation)
            ->setAction(QueuedNodeTranslationAction::ACTION_PUBLISH)
            ->setUser($user)
            ->setDate($date);
        $this->em->persist($queuedNodeTranslationAction);
        $this->em->flush();
    }

    /**
     * @throws AccessDeniedException
     */
    public function unPublish(NodeTranslation $nodeTranslation)
    {
        if (false === $this->authorizationChecker->isGranted(PermissionMap::PERMISSION_UNPUBLISH, $nodeTranslation->getNode())) {
            throw new AccessDeniedException();
        }

        $node = $nodeTranslation->getNode();
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();
        $page = $nodeVersion->getRef($this->em);

        $this->eventDispatcher->dispatch(
            new NodeEvent($node, $nodeTranslation, $nodeVersion, $page),
            Events::PRE_UNPUBLISH
        );
        $nodeTranslation->setOnline(false);
        $this->em->persist($nodeTranslation);
        $this->em->flush();

        // Remove scheduled task
        $this->unSchedulePublish($nodeTranslation);

        $this->eventDispatcher->dispatch(
            new NodeEvent($node, $nodeTranslation, $nodeVersion, $page),
            Events::POST_UNPUBLISH
        );
    }

    /**
     * @param NodeTranslation $nodeTranslation The NodeTranslation
     * @param \DateTime       $date            The date to unpublish
     *
     * @throws AccessDeniedException
     */
    public function unPublishLater(NodeTranslation $nodeTranslation, \DateTime $date)
    {
        $node = $nodeTranslation->getNode();
        if (false === $this->authorizationChecker->isGranted(PermissionMap::PERMISSION_UNPUBLISH, $node)) {
            throw new AccessDeniedException();
        }

        // remove existing first
        $this->unSchedulePublish($nodeTranslation);
        $user = $this->tokenStorage->getToken()->getUser();
        $queuedNodeTranslationAction = new QueuedNodeTranslationAction();
        $queuedNodeTranslationAction
            ->setNodeTranslation($nodeTranslation)
            ->setAction(QueuedNodeTranslationAction::ACTION_UNPUBLISH)
            ->setUser($user)
            ->setDate($date);
        $this->em->persist($queuedNodeTranslationAction);
        $this->em->flush();
    }

    public function unSchedulePublish(NodeTranslation $nodeTranslation)
    {
        // @var Node $node
        $queuedNodeTranslationAction = $this->em->getRepository(QueuedNodeTranslationAction::class)
            ->findOneBy(['nodeTranslation' => $nodeTranslation]);

        if (null !== $queuedNodeTranslationAction) {
            $this->em->remove($queuedNodeTranslationAction);
            $this->em->flush();
        }
    }

    /**
     * This shouldn't be here either but it's an improvement.
     *
     * @param HasNodeInterface $page            The page
     * @param NodeTranslation  $nodeTranslation The node translation
     * @param NodeVersion      $nodeVersion     The node version
     * @param BaseUser         $user            The user
     *
     * @return mixed
     */
    public function createPublicVersion(
        HasNodeInterface $page,
        NodeTranslation $nodeTranslation,
        NodeVersion $nodeVersion,
        $user
    ) {
        $newPublicPage = $this->cloneHelper->deepCloneAndSave($page);
        $newNodeVersion = $this->em->getRepository(NodeVersion::class)->createNodeVersionFor(
            $newPublicPage,
            $nodeTranslation,
            $user,
            $nodeVersion
        );

        $newNodeVersion
            ->setOwner($nodeVersion->getOwner())
            ->setUpdated($nodeVersion->getUpdated())
            ->setCreated($nodeVersion->getCreated());

        $nodeVersion
            ->setOwner($user)
            ->setCreated(new \DateTime())
            ->setOrigin($newNodeVersion);

        $this->em->persist($newNodeVersion);
        $this->em->persist($nodeVersion);
        $this->em->persist($nodeTranslation);
        $this->em->flush();
        $this->eventDispatcher->dispatch(
            new NodeEvent($nodeTranslation->getNode(), $nodeTranslation, $nodeVersion, $newPublicPage),
            Events::CREATE_PUBLIC_VERSION
        );

        return $newNodeVersion;
    }
}
