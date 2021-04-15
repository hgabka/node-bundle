<?php

namespace Hgabka\NodeBundle\Router;

use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Repository\NodeTranslationRepository;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * The SlugRouter takes care of routing the paths for slugs. It should have the
 * lowest priority as it's a catch-all router that routes (almost) all requests
 * to the SlugController.
 */
class SlugRouter implements RouterInterface, VersatileGeneratorInterface
{
    const STRATEGY_PREFIX = 'prefix';
    const STRATEGY_PREFIX_EXCEPT_DEFAULT = 'prefix_except_default';
    const STRATEGY_CUSTOM = 'custom';

    public static $SLUG = '_slug';
    public static $SLUG_PREVIEW = '_slug_preview';

    /** @var RequestContext */
    protected $context;

    /** @var RouteCollection */
    protected $routeCollection;

    /** @var UrlGenerator */
    protected $urlGenerator;

    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $slugPattern;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /**
     * The constructor for this service.
     *
     * @param ContainerInterface $container
     */
    public function __construct($container, HgabkaUtils $hgabkaUtils)
    {
        $this->container = $container;
        $this->slugPattern = "[a-zA-Z0-9\-_\/]*";
        $this->hgabkaUtils = $hgabkaUtils;
    }

    /**
     * Match given urls via the context to the routes we defined.
     * This functionality re-uses the default Symfony way of routing and its
     * components.
     *
     * @param string $pathinfo
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function match($pathinfo)
    {
        $urlMatcher = new UrlMatcher(
            $this->getRouteCollection(),
            $this->getContext()
        );
        $result = $urlMatcher->match($pathinfo);

        if (!empty($result)) {
            /** @var NodeTranslation $nodeTranslation */
            $nodeTranslation = $this->getNodeTranslation($result);
            if (null === $nodeTranslation) {
                throw new ResourceNotFoundException('No page found for slug '.$pathinfo);
            }
            $result['_nodeTranslation'] = $nodeTranslation;
            if (!isset($result['_locale']) || $result['_locale'] !== $nodeTranslation->getLang()) {
                $result['_locale'] = $nodeTranslation->getLang();
            }
        }

        return $result;
    }

    /**
     * Gets the request context.
     *
     * @return RequestContext The context
     *
     * @api
     */
    public function getContext()
    {
        if (!isset($this->context)) {
            /** @var Request $request */
            $request = $this->getMasterRequest();

            $this->context = new RequestContext();
            $this->context->fromRequest($request);
        }

        return $this->context;
    }

    /**
     * Sets the request context.
     *
     * @param RequestContext $context The context
     *
     * @api
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * Generate an url for a supplied route.
     *
     * @param string   $name          The path
     * @param array    $parameters    The route parameters
     * @param bool|int $referenceType The type of reference to be generated (one of the UrlGeneratorInterface constants)
     *
     * @return null|string
     */
    public function generate($name, $parameters = [], $referenceType = UrlGenerator::ABSOLUTE_PATH)
    {
        $strategy = $this->getRouteConfig()['strategy'];
        $prefixed = \in_array($strategy, [self::STRATEGY_PREFIX, self::STRATEGY_PREFIX_EXCEPT_DEFAULT], true);
        if (\in_array($name, ['_slug', '_slug_preview'], true) && \count($this->hgabkaUtils->getAvailableLocales()) > 1 && $prefixed) {
            $lang = isset($parameters['_locale']) ? $parameters['_locale'] : $this->hgabkaUtils->getCurrentLocale();
            $name .= '_'.$lang;
        }
        $this->urlGenerator = new UrlGenerator(
            $this->getRouteCollection(),
            $this->getContext()
        );

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    /**
     * Getter for routeCollection.
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection()
    {
        if (null === $this->routeCollection) {
            $strategy = $this->getRouteConfig()['strategy'];
            $prefixed = \in_array($strategy, [self::STRATEGY_PREFIX, self::STRATEGY_PREFIX_EXCEPT_DEFAULT], true);
            $this->routeCollection = new RouteCollection();
            $allLocales = $this->hgabkaUtils->getAvailableLocales();
            if (\count($allLocales) < 2 || !$prefixed) {
                $this->addPreviewRoute(current($allLocales), false);
                $this->addSlugRoute(current($allLocales), false);
            } else {
                foreach ($allLocales as $locale) {
                    if ($locale !== $this->hgabkaUtils->getDefaultLocale()) {
                        $this->addPreviewRoute($locale, $prefixed);
                        $this->addSlugRoute($locale, $prefixed);
                    }
                }
                $this->addPreviewRoute($this->hgabkaUtils->getDefaultLocale(), self::STRATEGY_PREFIX === $strategy);
                $this->addSlugRoute($this->hgabkaUtils->getDefaultLocale(), self::STRATEGY_PREFIX === $strategy);
            }
        }

        return $this->routeCollection;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    protected function getMasterRequest()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->container->get('request_stack');
        if (null === $requestStack) {
            return null;
        }

        return $requestStack->getMasterRequest();
    }

    /**
     * Add the preview route to the route collection.
     *
     * @param null|mixed $locale
     * @param mixed      $addLocale
     */
    protected function addPreviewRoute($locale = null, $addLocale = true)
    {
        $routeParameters = $this->getPreviewRouteParameters($locale, $addLocale);
        $this->addRoute(self::$SLUG_PREVIEW.($addLocale && $locale ? '_'.$locale : ''), $routeParameters);
    }

    /**
     * Add the slug route to the route collection.
     *
     * @param null|mixed $locale
     * @param mixed      $addLocale
     */
    protected function addSlugRoute($locale = null, $addLocale = true)
    {
        $routeParameters = $this->getSlugRouteParameters($locale, $addLocale);
        $this->addRoute(self::$SLUG.($addLocale && $locale ? '_'.$locale : ''), $routeParameters);
    }

    /**
     * Return preview route parameters.
     *
     * @param null|mixed $locale
     * @param mixed      $addLocale
     *
     * @return array
     */
    protected function getPreviewRouteParameters($locale = null, $addLocale = false)
    {
        $previewPath = '/preview'.$this->adjustPath($this->getRoutePattern());
        if ($locale) {
            if ($addLocale) {
                $previewPath = '/'.$locale.str_replace('/{_locale}', '', $previewPath);
            } else {
                $previewPath = str_replace('/{_locale}', '', $previewPath);
            }
        }

        $previewDefaults = [
            '_controller' => 'HgabkaNodeBundle:Slug:slug',
            'preview' => true,
            'url' => '',
            '_locale' => $locale ?: $this->getDefaultLocale(),
        ];
        $previewRequirements = [
            'url' => $this->getSlugPattern(),
        ];

        return [
            'path' => $previewPath,
            'defaults' => $previewDefaults,
            'requirements' => $previewRequirements,
        ];
    }

    /**
     * Return slug route parameters.
     *
     * @param null|mixed $locale
     * @param mixed      $addLocale
     *
     * @return array
     */
    protected function getSlugRouteParameters($locale = null, $addLocale = false)
    {
        $slugPath = $this->adjustPath($this->getRoutePattern());

        if ($locale) {
            if ($addLocale) {
                $slugPath = '/'.$locale.str_replace('/{_locale}', '', $slugPath);
            } else {
                $slugPath = str_replace('/{_locale}', '', $slugPath);
            }
        }

        $slugDefaults = [
            '_controller' => 'HgabkaNodeBundle:Slug:slug',
            'preview' => false,
            'url' => '',
            '_locale' => $locale ?: $this->getDefaultLocale(),
        ];
        $slugRequirements = [
            'url' => $this->getSlugPattern(),
        ];

        return [
            'path' => $slugPath,
            'defaults' => $slugDefaults,
            'requirements' => $slugRequirements,
        ];
    }

    protected function adjustPath($path)
    {
        return '/{_locale}'.str_replace('/{_locale}', '', $path);
    }

    protected function getRouteConfig()
    {
        return $this->container->getParameter('hgabka_node.route_config');
    }

    protected function getRoutePattern()
    {
        $pattern = $this->getRouteConfig()['pattern'];
        if (isset($pattern[$this->hgabkaUtils->getCurrentLocale()])) {
            $slugPattern = $pattern[$this->hgabkaUtils->getCurrentLocale()];
        } else {
            $slugPattern = $pattern['default'];
        }

        return \count($this->hgabkaUtils->getAvailableLocales()) < 2
            ? str_replace('/{_locale}', '', $slugPattern)
            : $slugPattern
            ;
    }

    /**
     * @return string
     */
    protected function getDefaultLocale()
    {
        return $this->hgabkaUtils->getDefaultLocale();
    }

    /**
     * @return string
     */
    protected function getSlugPattern()
    {
        return $this->slugPattern;
    }

    /**
     * @param string $name
     */
    protected function addRoute($name, array $parameters = [])
    {
        $this->routeCollection->add(
            $name,
            new Route(
                $parameters['path'],
                $parameters['defaults'],
                $parameters['requirements']
            )
        );
    }

    /**
     * @param array $matchResult
     *
     * @return \Kunstmaan\NodeBundle\Entity\NodeTranslation
     */
    protected function getNodeTranslation($matchResult)
    {
        // The route matches, now check if it actually exists (needed for proper chain router chaining!)
        $nodeTranslationRepo = $this->getNodeTranslationRepository();

        // @var NodeTranslation $nodeTranslation
        $nodeTranslation = $nodeTranslationRepo->getNodeTranslationForUrl(
            $matchResult['url'],
            $matchResult['_locale']
        );

        return $nodeTranslation;
    }

    /**
     * @return \Kunstmaan\NodeBundle\Repository\NodeTranslationRepository
     */
    protected function getNodeTranslationRepository()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        // @var NodeTranslationRepository $nodeTranslationRepo
        $nodeTranslationRepo = $em->getRepository(
            NodeTranslation::class
        );

        return $nodeTranslationRepo;
    }

    /**
     * @param array $locales
     *
     * @return string
     */
    protected function getEscapedLocales($locales)
    {
        $escapedLocales = [];
        foreach ($locales as $locale) {
            $escapedLocales[] = str_replace('-', '\-', $locale);
        }

        return implode('|', $escapedLocales);
    }
    
    public function supports($name)
    {
        return 0 === strpos($name, '_slug');
    }

    public function getRouteDebugMessage($name, array $parameters = [])
    {
        return 'Node bundle rote';
    }
}
