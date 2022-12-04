<?php

namespace Hgabka\NodeBundle\EventListener;

use Hgabka\NodeBundle\Event\CopyPageTranslationNodeEvent;
use Hgabka\NodeBundle\Event\Events;
use Hgabka\NodeBundle\Event\NodeEvent;
use Hgabka\NodeBundle\Event\RecopyPageTranslationNodeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LogPageEventsSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserInterface
     */
    private ?UserInterface $user = null;

    public function __construct(private readonly LoggerInterface $logger, private readonly Security $security) {}

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::COPY_PAGE_TRANSLATION => 'onCopyPageTranslation',
            Events::RECOPY_PAGE_TRANSLATION => 'onRecopyPageTranslation',
            Events::ADD_EMPTY_PAGE_TRANSLATION => 'onAddEmptyPageTranslation',
            Events::POST_PUBLISH => 'postPublish',
            Events::POST_UNPUBLISH => 'postUnPublish',
            Events::POST_DELETE => 'postDelete',
            Events::ADD_NODE => 'onAddNode',
            Events::POST_PERSIST => 'postPersist',
            Events::CREATE_PUBLIC_VERSION => 'onCreatePublicVersion',
            Events::CREATE_DRAFT_VERSION => 'onCreateDraftVersion',
        ];
    }

    public function onCopyPageTranslation(CopyPageTranslationNodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just copied the page translation from %s (%d) to %s (%d) for node with id %d', $this->getUser()->getUserIdentifier(), $event->getOriginalLanguage(), $event->getOriginalPage()->getId(), $event->getNodeTranslation()->getLang(), $event->getPage()->getId(), $event->getNode()->getId()));
    }

    public function onRecopyPageTranslation(RecopyPageTranslationNodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just recopied the page translation from %s (%d) to %s (%d) for node with id %d', $this->getUser()->getUserIdentifier(), $event->getOriginalLanguage(), $event->getOriginalPage()->getId(), $event->getNodeTranslation()->getLang(), $event->getPage()->getId(), $event->getNode()->getId()));
    }

    public function onAddEmptyPageTranslation(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just added an empty page translation (%d) for node with id %d in language %s', $this->getUser()->getUserIdentifier(), $event->getPage()->getId(), $event->getNode()->getId(), $event->getNodeTranslation()->getLang()));
    }

    public function postPublish(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just published the page with id %d for node %d in language %s', $this->getUser()->getUserIdentifier(), $event->getPage()->getId(), $event->getNode()->getId(), $event->getNodeTranslation()->getLang()));
    }

    public function postUnPublish(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just unpublished the page with id %d for node %d in language %s', $this->getUser()->getUserIdentifier(), $event->getPage()->getId(), $event->getNode()->getId(), $event->getNodeTranslation()->getLang()));
    }

    public function postDelete(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just deleted node with id %d', $this->getUser()->getUserIdentifier(), $event->getNode()->getId()));
    }

    public function onAddNode(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just added node with id %d in language %s', $this->getUser()->getUserIdentifier(), $event->getNode()->getId(), $event->getNodeTranslation()->getLang()));
    }

    public function postPersist(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just updated page with id %d for node %d in language %s', $this->getUser()->getUserIdentifier(), $event->getPage()->getId(), $event->getNode()->getId(), $event->getNodeTranslation()->getLang()));
    }

    public function onCreatePublicVersion(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just created a new public version %d for node %d in language %s', $this->getUser()->getUserIdentifier(), $event->getNodeVersion()->getId(), $event->getNode()->getId(), $event->getNodeTranslation()->getLang()));
    }

    public function onCreateDraftVersion(NodeEvent $event): void
    {
        $this->logger->info(sprintf('%s just created a draft version %d for node %d in language %s', $this->getUser()->getUserIdentifier(), $event->getNodeVersion()->getId(), $event->getNode()->getId(), $event->getNodeTranslation()->getLang()));
    }

    private function getUser(): ?UserInterface
    {
        if (null === $this->user) {
            $this->user = $this->security->getUser();
        }

        return $this->user;
    }
}
