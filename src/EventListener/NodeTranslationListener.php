<?php

namespace Hgabka\NodeBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Helper\PagesConfiguration;
use Hgabka\NodeBundle\Repository\NodeTranslationRepository;
use Hgabka\UtilsBundle\FlashMessages\FlashTypes;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Hgabka\UtilsBundle\Helper\SlugifierInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Listens to doctrine postFlush event and updates
 * the urls if the entities are nodetranslations.
 */
class NodeTranslationListener
{
    private $logger;
    private $nodeTranslations;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var HgabkaUtils
     */
    private $hgabkaUtils;

    /**
     * @var PagesConfiguration
     */
    private $pagesConfiguration;

    /**
     * @param Logger $logger The logger
     */
    public function __construct(
        RequestStack $requestStack,
        $logger,
        SlugifierInterface $slugifier,
        HgabkaUtils $hgabkaUtils,
        PagesConfiguration $pagesConfiguration
    ) {
        $this->nodeTranslations = [];
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->slugifier = $slugifier;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->pagesConfiguration = $pagesConfiguration;
    }

    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof NodeTranslation) {
            $this->setSlugWhenEmpty($entity, $args->getEntityManager());
            $this->ensureSlugIsSlugified($entity);
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof NodeTranslation) {
            $this->setSlugWhenEmpty($entity, $args->getEntityManager());
            $this->ensureSlugIsSlugified($entity);
        }
    }

    /**
     * onFlush doctrine event - collect all nodetranslations in scheduled
     * entity updates here.
     *
     * @param OnFlushEventArgs $args
     *
     * Note: only needed because scheduled entity updates are not accessible in
     * postFlush
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        // Collect all nodetranslations that are updated
        foreach ($em->getUnitOfWork()->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof NodeTranslation) {
                $this->nodeTranslations[] = $entity;
            }
        }
    }

    /**
     * PostUpdate doctrine event - updates the nodetranslation urls if needed.
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        foreach ($this->nodeTranslations as $entity) {
            /** @var $entity NodeTranslation */
            if ($entity instanceof NodeTranslation) {
                $publicNodeVersion = $entity->getPublicNodeVersion();

                /** @var $publicNodeVersion NodeVersion */
                $publicNode = $publicNodeVersion->getRef($em);

                // Do nothing for StructureNode objects, skip
                if ($publicNode instanceof HasNodeInterface && $publicNode->isStructureNode()
                ) {
                    continue;
                }

                $entity = $this->updateUrl($entity, $em);

                if (false !== $entity) {
                    $em->persist($entity);
                    $em->flush($entity);

                    $this->updateNodeChildren($entity, $em);
                }
            }
        }
    }

    protected function getSession(): ?SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (Throwable $e) {
            return null;
        }
    }

    private function setSlugWhenEmpty(
        NodeTranslation $nodeTranslation,
        EntityManager $em
    ) {
        $publicNode = $nodeTranslation->getRef($em);

        // Do nothing for StructureNode objects, skip
        if ($publicNode instanceof HasNodeInterface && $publicNode->isStructureNode()
        ) {
            return;
        }

        // If no slug is set and no structure node, apply title as slug
        if (null === $nodeTranslation->getSlug() && null !== $nodeTranslation->getNode()
                                                                             ->getParent()
        ) {
            $nodeTranslation->setSlug(
                $this->slugifier->slugify($nodeTranslation->getTitle())
            );
        }
    }

    private function ensureSlugIsSlugified(NodeTranslation $nodeTranslation)
    {
        if (null !== $nodeTranslation->getSlug()) {
            $nodeTranslation->setSlug(
                $this->slugifier->slugify($nodeTranslation->getSlug())
            );
        }
    }

    /**
     * Checks if a nodetranslation has children and update their url.
     *
     * @param NodeTranslation $node The node
     * @param EntityManager   $em   The entity manager
     */
    private function updateNodeChildren(
        NodeTranslation $node,
        EntityManager $em
    ) {
        $children = $node->getNode()->getChildren();
        if (\count($children) > 0) {
            // @var Node $child
            foreach ($children as $child) {
                $translation = $child->getNodeTranslation(
                    $node->getLang(),
                    true
                );
                if ($translation) {
                    $translation = $this->updateUrl($translation, $em);

                    if (false !== $translation) {
                        $em->persist($translation);
                        $em->flush($translation);

                        $this->updateNodeChildren($translation, $em);
                    }
                }
            }
        }
    }

    /**
     * Update the url for a nodetranslation.
     *
     * @param NodeTranslation $nodeTranslation The node translation
     * @param EntityManager   $em              The entity manager
     *
     * @return bool|NodeTranslation returns the node when all is well because
     *                              it has to be saved
     */
    private function updateUrl(NodeTranslation $nodeTranslation, $em)
    {
        $result = $this->ensureUniqueUrl($nodeTranslation, $em);

        if ($result) {
            return $nodeTranslation;
        }

        $this->logger->info(
            'Found NT ' . $nodeTranslation->getId() . ' needed NO change'
        );

        return false;
    }

    /**
     * @param NodeTranslation $translation The node translation
     * @param EntityManager   $em          The entity manager
     * @param array           $flashes     Flashes
     *
     * A function that checks the URL and sees if it's unique.
     * It's allowed to be the same when the node is a StructureNode.
     * When a node is deleted it needs to be ignored in the check.
     * Offline nodes need to be included as well.
     *
     * It sluggifies the slug, updates the URL
     * and checks all existing NodeTranslations ([1]), excluding itself. If a
     * URL existsthat has the same url. If an existing one is found the slug is
     * modified, the URL is updated and the check is repeated until no prior
     * urls exist.
     *
     * NOTE: We need a way to tell if the slug has been modified or not.
     * NOTE: Would be cool if we could increment a number after the slug. Like
     * check if it matches -v# and increment the number.
     *
     * [1] For all languages for now. The issue is that we need a way to know
     * if a node's URL is prepended with the language or not. For now both
     * scenarios are possible so we check for all languages.
     * @param NodeTranslation &$translation Reference to the NodeTranslation.
     *                                      This is modified in place.
     * @param EntityManager   $em           The entity manager
     * @param array           $flashes      The flash messages array
     *
     * @return bool
     * @return bool
     */
    private function ensureUniqueUrl(
        NodeTranslation &$translation,
        EntityManager $em,
        $flashes = []
    ) {
        // Can't use GetRef here yet since the NodeVersions aren't loaded yet for some reason.
        $nodeVersion = $translation->getPublicNodeVersion();
        $page = $em->getRepository($nodeVersion->getRefEntityName())
                   ->find($nodeVersion->getRefId())
        ;
        $isStructureNode = $page->isStructureNode();

        // If it's a StructureNode the slug and url should be empty.
        if ($isStructureNode) {
            $translation->setSlug('');
            $translation->setUrl($translation->getFullSlug());

            return true;
        }

        // @var NodeTranslationRepository $nodeTranslationRepository
        $nodeTranslationRepository = $em->getRepository(
            NodeTranslation::class
        );

        if ($translation->getUrl() === $translation->getFullSlug()) {
            $this->logger->debug(
                'Evaluating URL for NT ' . $translation->getId() .
                ' getUrl: \'' . $translation->getUrl() . '\' getFullSlug: \'' .
                $translation->getFullSlug() . '\''
            );

            return false;
        }

        // Adjust the URL.
        $translation->setUrl($translation->getFullSlug());

        // Find all translations with this new URL, whose nodes are not deleted.
        $translations = $nodeTranslationRepository->getAllNodeTranslationsForUrl(
            $translation->getUrl(),
            $translation->getLang(),
            false,
            $translation,
            null
        );

        $this->logger->debug(
            'Found ' . \count(
                $translations
            ) . ' node(s) that match url \'' . $translation->getUrl() . '\''
        );

        $translationsWithSameUrl = [];

        /** @var NodeTranslation $trans */
        foreach ($translations as $trans) {
            if (!$this->pagesConfiguration->isStructureNode($trans->getPublicNodeVersion()->getRefEntityName())) {
                $translationsWithSameUrl[] = $trans;
            }
        }

        if (\count($translations) > 0) {
            $oldUrl = $translation->getFullSlug();
            $translation->setSlug(
                $this->slugifier->slugify(
                    $this->incrementString($translation->getSlug())
                )
            );
            $newUrl = $translation->getFullSlug();

            $message = 'Az oldal URL megváltoztatásra került erről: ' . $oldUrl . ' erre: ' . $newUrl . ', mert egy másik oldalhoz már használatban van az URL.';
            $this->logger->info($message);
            $flashes[] = $message;

            $this->ensureUniqueUrl($translation, $em, $flashes);
        } elseif (\count($flashes) > 0 && $this->isInRequestScope()) {
            // No translations found so we're certain we can show this message.
            $flash = current(\array_slice($flashes, -1));
            $session = $this->getSession();
            if ($session) {
                $session->getFlashBag()->add(FlashTypes::WARNING, $flash);
            }
        }

        return true;
    }

    /**
     * Increment a string that ends with a number.
     * If the string does not end in a number we'll add the append and then add
     * the first number.
     *
     * @param string $string the string we want to increment
     * @param string $append the part we want to append before we start adding
     *                       a number
     *
     * @return string incremented string
     */
    private static function incrementString($string, $append = '-v')
    {
        $finalDigitGrabberRegex = '/\d+$/';
        $matches = [];

        preg_match($finalDigitGrabberRegex, $string, $matches);

        if (\count($matches) > 0) {
            $digit = (int) $matches[0];
            ++$digit;

            // Replace the integer with the new digit.
            return preg_replace($finalDigitGrabberRegex, $digit, $string);
        }

        return $string . $append . '1';
    }

    private function isInRequestScope()
    {
        return $this->requestStack && $this->requestStack->getCurrentRequest();
    }
}
