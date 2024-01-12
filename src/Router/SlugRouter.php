<?php

namespace Hgabka\NodeBundle\Router;

use Hgabka\NodeBundle\Controller\SlugController;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Repository\NodeTranslationRepository;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    public const string STRATEGY_PREFIX = 'prefix';
    public const string STRATEGY_PREFIX_EXCEPT_DEFAULT = 'prefix_except_default';
    public const string STRATEGY_CUSTOM = 'custom';

    public static string $SLUG = '_slug';
    public static string $SLUG_PREVIEW = '_slug_preview';

    protected ?RequestContext $context = null;

    protected ?RouteCollection $routeCollection = null;

    protected ?UrlGenerator $urlGenerator = null;

    protected ?ContainerInterface $container = null;

    protected ?string $slugPattern = null;

    protected ?HgabkaUtils $hgabkaUtils = null;

    public function __construct($container, HgabkaUtils $hgabkaUtils)
    {
        $this->container = $container;
        $this->slugPattern = "[a-zA-Z0-9\-_\/]*";
        $this->hgabkaUtils = $hgabkaUtils;
    }

    public function match(string $pathinfo): array
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
                throw new ResourceNotFoundException('No page found for slug ' . $pathinfo);
            }
            $result['_nodeTranslation'] = $nodeTranslation;
            if (!isset($result['_locale']) || $result['_locale'] !== $nodeTranslation->getLang()) {
                $result['_locale'] = $nodeTranslation->getLang();
            }
        }

        return $result;
    }

    public function getContext(): RequestContext
    {
        if (!isset($this->context)) {
            /** @var Request $request */
            $request = $this->getMasterRequest();

            $this->context = new RequestContext();
            $this->context->fromRequest($request);
        }

        return $this->context;
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function generate(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $strategy = $this->getRouteConfig()['strategy'];
        $prefixed = \in_array($strategy, [self::STRATEGY_PREFIX, self::STRATEGY_PREFIX_EXCEPT_DEFAULT], true);
        if (self::STRATEGY_PREFIX_EXCEPT_DEFAULT === $strategy
            && ((isset($parameters['_locale']) && $parameters['_locale'] === $this->hgabkaUtils->getDefaultLocale())
                || !isset($parameters['_locale']) && $this->hgabkaUtils->getCurrentLocale() === $this->hgabkaUtils->getDefaultLocale())
        ) {
            $prefixed = false;
        }

        if (\in_array($name, [self::$SLUG, self::$SLUG_PREVIEW], true) && \count($this->hgabkaUtils->getAvailableLocales()) > 1 && $prefixed) {
            $lang = $parameters['_locale'] ?? $this->hgabkaUtils->getCurrentLocale();
            $name .= '_' . $lang;
        }
        $this->urlGenerator = new UrlGenerator(
            $this->getRouteCollection(),
            $this->getContext()
        );

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    public function getRouteCollection(): RouteCollection
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

    public function supports(string $name): bool
    {
        return str_starts_with($name, self::$SLUG);
    }

    public function getRouteDebugMessage(string $name, array $parameters = []): string
    {
        return 'Node bundle route';
    }

    protected function getMasterRequest(): ?Request
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->container->get('request_stack');
        if (null === $requestStack) {
            return null;
        }

        return $requestStack->getMainRequest();
    }

    protected function addPreviewRoute(?string $locale = null, bool $addLocale = true): void
    {
        $routeParameters = $this->getPreviewRouteParameters($locale, $addLocale);
        $this->addRoute(self::$SLUG_PREVIEW . ($addLocale && $locale ? '_' . $locale : ''), $routeParameters);
    }

    protected function addSlugRoute(?string $locale = null, bool $addLocale = true): void
    {
        $routeParameters = $this->getSlugRouteParameters($locale, $addLocale);
        $this->addRoute(self::$SLUG . ($addLocale && $locale ? '_' . $locale : ''), $routeParameters);
    }

    protected function getPreviewRouteParameters(?string $locale = null, bool $addLocale = false): array
    {
        $previewPath = '/preview' . $this->adjustPath($this->getRoutePattern());
        if ($locale) {
            if ($addLocale) {
                $previewPath = '/' . $locale . str_replace('/{_locale}', '', $previewPath);
            } else {
                $previewPath = str_replace('/{_locale}', '', $previewPath);
            }
        }

        $previewDefaults = [
            '_controller' => SlugController::class . '::slug',
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

    protected function getSlugRouteParameters(?string $locale = null, bool $addLocale = false): array
    {
        $slugPath = $this->adjustPath($this->getRoutePattern());

        if ($locale) {
            if ($addLocale) {
                $slugPath = '/' . $locale . str_replace('/{_locale}', '', $slugPath);
            } else {
                $slugPath = str_replace('/{_locale}', '', $slugPath);
            }
        }

        $slugDefaults = [
            '_controller' => SlugController::class . '::slug',
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

    protected function adjustPath(string $path): string
    {
        return '/{_locale}' . str_replace('/{_locale}', '', $path);
    }

    protected function getRouteConfig(): array
    {
        return $this->container->getParameter('hgabka_node.route_config');
    }

    protected function getRoutePattern(): string
    {
        $pattern = $this->getRouteConfig()['pattern'];
        if (isset($pattern[$this->hgabkaUtils->getCurrentLocale()])) {
            $slugPattern = $pattern[$this->hgabkaUtils->getCurrentLocale()];
        } else {
            $slugPattern = $pattern['default'];
        }

        return \count($this->hgabkaUtils->getAvailableLocales()) < 2
            ? str_replace('/{_locale}', '', $slugPattern)
            : $slugPattern;
    }

    /**
     * @return string
     */
    protected function getDefaultLocale(): string
    {
        return $this->hgabkaUtils->getDefaultLocale();
    }

    /**
     * @return string
     */
    protected function getSlugPattern(): string
    {
        return $this->slugPattern;
    }

    protected function addRoute(string $name, array $parameters = []): void
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

    protected function getNodeTranslation(array $matchResult): ?NodeTranslation
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

    protected function getNodeTranslationRepository(): NodeTranslationRepository
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        // @var NodeTranslationRepository $nodeTranslationRepo
        $nodeTranslationRepo = $em->getRepository(
            NodeTranslation::class
        );

        return $nodeTranslationRepo;
    }

    protected function getEscapedLocales(array $locales): string
    {
        $escapedLocales = [];
        foreach ($locales as $locale) {
            $escapedLocales[] = str_replace('-', '\-', $locale);
        }

        return implode('|', $escapedLocales);
    }
}
