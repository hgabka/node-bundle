<?php

namespace Hgabka\NodeBundle\Helper;

use Doctrine\ORM\EntityManager;
use Hgabka\NodeBundle\Validation\URLValidator;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * A helper for replacing url's.
 */
class URLHelper
{
    use URLValidator;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var null|array
     */
    private $nodeTranslationMap;

    /**
     * @var null|array
     */
    private $mediaMap;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var HgabkaUtils
     */
    private $hgabkaUtils;

    /**
     * @param EntityManager   $em
     * @param RouterInterface $router
     * @param LoggerInterface $logger
     * @param HgabkaUtils     $hgabkaUtils
     * @param RequestStack    $requestStack
     */
    public function __construct(EntityManager $em, RouterInterface $router, LoggerInterface $logger, RequestStack $requestStack, HgabkaUtils $hgabkaUtils)
    {
        $this->em = $em;
        $this->router = $router;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->hgabkaUtils = $hgabkaUtils;
    }

    /**
     * Replace a given text, according to the node translation id and the multidomain site id.
     *
     * @param $text
     *
     * @return mixed
     */
    public function replaceUrl($text)
    {
        if ($this->isEmailAddress($text)) {
            $text = sprintf('%s:%s', 'mailto', $text);
        }

        if ($this->isInternalLink($text)) {
            preg_match_all("/\[(([a-z_A-Z]+):)?NT([0-9]+)\]/", $text, $matches, PREG_SET_ORDER);

            if (\count($matches) > 0) {
                $map = $this->getNodeTranslationMap();
                foreach ($matches as $match) {
                    $nodeTranslationFound = false;
                    $fullTag = $match[0];
                    $hostId = $match[2];
                    $hostConfig = null;
                    $hostBaseUrl = null;

                    $nodeTranslationId = $match[3];

                    foreach ($map as $nodeTranslation) {
                        if ($nodeTranslation['id'] === $nodeTranslationId) {
                            $urlParams = ['url' => $nodeTranslation['url']];
                            $nodeTranslationFound = true;
                            // Only add locale if multilingual site

                            $url = $this->router->generate('_slug', $urlParams);

                            $text = str_replace($fullTag, $url, $text);
                        }
                    }

                    if (!$nodeTranslationFound) {
                        $this->logger->error('No NodeTranslation found in the database when replacing url tag '.$fullTag);
                    }
                }
            }
        }

        if ($this->isInternalMediaLink($text)) {
            preg_match_all("/\[(([a-z_A-Z]+):)?M([0-9]+)\]/", $text, $matches, PREG_SET_ORDER);

            if (\count($matches) > 0) {
                $map = $this->getMediaMap();
                foreach ($matches as $match) {
                    $mediaFound = false;
                    $fullTag = $match[0];
                    $mediaId = $match[3];

                    foreach ($map as $mediaItem) {
                        if ($mediaItem['id'] === $mediaId) {
                            $mediaFound = true;
                            $text = str_replace($fullTag, $mediaItem['url'], $text);
                        }
                    }

                    if (!$mediaFound) {
                        $this->logger->error('No Media found in the database when replacing url tag '.$fullTag);
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Get a map of all node translations. Only called once for caching.
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return null|array
     */
    private function getNodeTranslationMap()
    {
        if (null === $this->nodeTranslationMap) {
            $sql = 'SELECT id, url, lang FROM kuma_node_translations';
            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute();
            $this->nodeTranslationMap = $stmt->fetchAll();
        }

        return $this->nodeTranslationMap;
    }

    /**
     * Get a map of all media items. Only called once for caching.
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return null|array
     */
    private function getMediaMap()
    {
        if (null === $this->mediaMap) {
            $sql = 'SELECT id, url FROM kuma_media';
            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute();
            $this->mediaMap = $stmt->fetchAll();
        }

        return $this->mediaMap;
    }
}
