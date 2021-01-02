<?php

namespace Hgabka\NodeBundle\Helper\Menu;

use Doctrine\ORM\EntityManager;
use Hgabka\NodeBundle\Admin\NodeAdmin;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Entity\QueuedNodeTranslationAction;
use Hgabka\NodeBundle\Event\ConfigureActionMenuEvent;
use Hgabka\NodeBundle\Event\Events;
use Hgabka\NodeBundle\Helper\PagesConfiguration;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActionsMenuBuilder
{
    /** @var HgabkaUtils */
    protected $utils;

    /** @var RequestStack */
    protected $requestStack;

    /** @var Pool */
    protected $adminPool;

    /** @var NodeAdmin */
    protected $nodeAdmin;
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var NodeVersion
     */
    private $activeNodeVersion;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var PagesConfiguration
     */
    private $pagesConfiguration;

    /**
     * @var bool
     */
    private $isEditableNode = true;

    /**
     * @param FactoryInterface              $factory              The factory
     * @param EntityManager                 $em                   The entity manager
     * @param RouterInterface               $router               The router
     * @param EventDispatcherInterface      $dispatcher           The event dispatcher
     * @param AuthorizationCheckerInterface $authorizationChecker The security authorization checker
     */
    public function __construct(
        FactoryInterface $factory,
        EntityManager $em,
        RouterInterface $router,
        EventDispatcherInterface $dispatcher,
        AuthorizationCheckerInterface $authorizationChecker,
        PagesConfiguration $pagesConfiguration,
        Pool $adminPool,
        HgabkaUtils $utils,
        RequestStack $requestStack
    ) {
        $this->factory = $factory;
        $this->em = $em;
        $this->router = $router;
        $this->dispatcher = $dispatcher;
        $this->authorizationChecker = $authorizationChecker;
        $this->pagesConfiguration = $pagesConfiguration;
        $this->adminPool = $adminPool;
        $this->nodeAdmin = $adminPool->getAdminByAdminCode('hgabka_node.admin.node');
        $this->utils = $utils;
        $this->requestStack = $requestStack;
    }

    /**
     * @return ItemInterface
     */
    public function createSubActionsMenu()
    {
        $activeNodeVersion = $this->getActiveNodeVersion();
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'page-sub-actions');

        $utils = $this->utils;
        if ($utils->getAvailableLocales() > 1) {
            $menu->addChild(
                'subaction.langversions',
                [
                ]
            );
        }
        if (null !== $activeNodeVersion && $this->isEditableNode) {
            $menu->addChild(
                'subaction.versions',
                [
                    'linkAttributes' => [
                        'data-toggle' => 'modal',
                        'data-keyboard' => 'true',
                        'data-target' => '#versions',
                    ],
                ]
            );
        }

        $this->dispatcher->dispatch(
            Events::CONFIGURE_SUB_ACTION_MENU,
            new ConfigureActionMenuEvent(
                $this->factory,
                $menu,
                $activeNodeVersion
            )
        );

        return $menu;
    }

    /**
     * @return ItemInterface
     */
    public function createActionsMenu()
    {
        $activeNodeVersion = $this->getActiveNodeVersion();

        $translations = $activeNodeVersion->getNodeTranslation()->getNode()->getNodeTranslations(true);
        $canRecopy = false;
        if ($this->nodeAdmin->hasAccess('copy')) {
            foreach ($translations as $translation) {
                if ($translation->getLang() !== $activeNodeVersion->getNodeTranslation()->getLang()) {
                    $canRecopy = true;
                }
            }
        }

        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute(
            'class',
            'page-main-actions js-auto-collapse-buttons'
        );
        $menu->setChildrenAttribute(
            'data-visible-buttons',
            '3'
        );

        if (null === $activeNodeVersion) {
            $this->dispatcher->dispatch(
                Events::CONFIGURE_ACTION_MENU,
                new ConfigureActionMenuEvent(
                    $this->factory,
                    $menu,
                    $activeNodeVersion
                )
            );

            return $menu;
        }

        $activeNodeTranslation = $activeNodeVersion->getNodeTranslation();
        $node = $activeNodeTranslation->getNode();
        $queuedNodeTranslationAction = $this->em->getRepository(
            QueuedNodeTranslationAction::class
        )->findOneBy(['nodeTranslation' => $activeNodeTranslation]);

        $isFirst = true;
        $canEdit = $this->authorizationChecker->isGranted(PermissionMap::PERMISSION_EDIT, $node) && $this->nodeAdmin->hasAccess('edit');
        $canPublish = $this->authorizationChecker->isGranted(PermissionMap::PERMISSION_PUBLISH, $node) && $this->nodeAdmin->hasAccess('publish');

        if ($activeNodeVersion->isDraft() && $this->isEditableNode) {
            if ($canEdit) {
                $menu->addChild(
                    'action.saveasdraft',
                    [
                        'linkAttributes' => [
                            'type' => 'submit',
                            'class' => 'js-save-btn btn btn--raise-on-hover btn-primary',
                            'value' => 'save',
                            'name' => 'save',
                        ],
                        'extras' => ['renderType' => 'button'],
                    ]
                );
                if ($canRecopy) {
                    $menu->addChild(
                        'action.recopyfromlanguage',
                        [
                            'linkAttributes' => [
                                'class' => 'btn btn-default btn--raise-on-hover',
                                'data-toggle' => 'modal',
                                'data-keyboard' => 'true',
                                'data-target' => '#recopy',
                            ],
                        ]
                    );
                }
                $isFirst = false;
            }

            $previewParams = [
                'url' => $activeNodeTranslation->getUrl(),
                'version' => $activeNodeVersion->getId(),
            ];

            if (\count($this->utils->getAvailableLocales()) > 1) {
                $previewParams['_locale'] = $activeNodeTranslation->getLang();
            }

            $menu->addChild(
                'action.preview',
                [
                    'uri' => $this->router->generate(
                        '_slug_preview',
                        $previewParams
                    ),
                    'linkAttributes' => [
                        'target' => '_blank',
                        'class' => 'btn btn-default btn--raise-on-hover',
                    ],
                ]
            );

            if (empty($queuedNodeTranslationAction) && $canPublish) {
                $menu->addChild(
                    'action.publish',
                    [
                        'linkAttributes' => [
                            'data-toggle' => 'modal',
                            'data-target' => '#pub',
                            'class' => 'btn btn--raise-on-hover'.($isFirst ? ' btn-primary btn-save' : ' btn-default'),
                        ],
                    ]
                );
            }
        } else {
            if ($canEdit && $canPublish) {
                $menu->addChild(
                    'action.save',
                    [
                        'linkAttributes' => [
                            'type' => 'submit',
                            'class' => 'js-save-btn btn btn--raise-on-hover btn-primary',
                            'value' => 'save',
                            'name' => 'save',
                        ],
                        'extras' => ['renderType' => 'button'],
                    ]
                );
                $isFirst = false;
            }

            if ($this->isEditableNode) {
                $previewParams = [
                    'url' => $activeNodeTranslation->getUrl(),
                ];

                if (\count($this->utils->getAvailableLocales()) > 1) {
                    $previewParams['_locale'] = $activeNodeTranslation->getLang();
                }

                $menu->addChild(
                    'action.preview',
                    [
                        'uri' => $this->router->generate(
                            '_slug_preview',
                            $previewParams
                        ),
                        'linkAttributes' => [
                            'target' => '_blank',
                            'class' => 'btn btn-default btn--raise-on-hover',
                        ],
                    ]
                );

                if (empty($queuedNodeTranslationAction)
                    && $activeNodeTranslation->isOnline()
                    && $this->authorizationChecker->isGranted(
                        PermissionMap::PERMISSION_UNPUBLISH,
                        $node
                    )
                    && $this->nodeAdmin->hasAccess('unpublish')
                ) {
                    $menu->addChild(
                        'action.unpublish',
                        [
                            'linkAttributes' => [
                                'class' => 'btn btn-default btn--raise-on-hover',
                                'data-toggle' => 'modal',
                                'data-keyboard' => 'true',
                                'data-target' => '#unpub',
                            ],
                        ]
                    );
                } elseif (empty($queuedNodeTranslationAction)
                    && !$activeNodeTranslation->isOnline()
                    && $canPublish
                ) {
                    $menu->addChild(
                        'action.publish',
                        [
                            'linkAttributes' => [
                                'class' => 'btn btn-default btn--raise-on-hover',
                                'data-toggle' => 'modal',
                                'data-keyboard' => 'true',
                                'data-target' => '#pub',
                            ],
                        ]
                    );
                }

                if ($canEdit) {
                    $menu->addChild(
                        'action.saveasdraft',
                        [
                            'linkAttributes' => [
                                'type' => 'submit',
                                'class' => 'btn btn--raise-on-hover'.($isFirst ? ' btn-primary btn-save' : ' btn-default'),
                                'value' => 'saveasdraft',
                                'name' => 'saveasdraft',
                            ],
                            'extras' => ['renderType' => 'button'],
                        ]
                    );
                    if ($canRecopy) {
                        $menu->addChild(
                            'action.recopyfromlanguage',
                            [
                                'linkAttributes' => [
                                    'class' => 'btn btn-default btn--raise-on-hover',
                                    'data-toggle' => 'modal',
                                    'data-keyboard' => 'true',
                                    'data-target' => '#recopy',
                                ],
                            ]
                        );
                    }
                }
            }
        }

        if ($this->pagesConfiguration->getPossibleChildTypes(
            $node->getRefEntityName()
        )
            && $this->nodeAdmin->hasAccess('create')
        ) {
            $menu->addChild(
                'action.addsubpage',
                [
                    'linkAttributes' => [
                        'type' => 'button',
                        'class' => 'btn btn-default btn--raise-on-hover',
                        'data-toggle' => 'modal',
                        'data-keyboard' => 'true',
                        'data-target' => '#add-subpage-modal',
                    ],
                    'extras' => ['renderType' => 'button'],
                ]
            );
        }

        if (null !== $node->getParent() && $canEdit && $this->nodeAdmin->hasAccess('duplicate')) {
            $menu->addChild(
                'action.duplicate',
                [
                    'linkAttributes' => [
                        'type' => 'button',
                        'class' => 'btn btn-default btn--raise-on-hover',
                        'data-toggle' => 'modal',
                        'data-keyboard' => 'true',
                        'data-target' => '#duplicate-page-modal',
                    ],
                    'extras' => ['renderType' => 'button'],
                ]
            );
        }

        if ((null !== $node->getParent() || $node->getChildren()->isEmpty())
            && $this->authorizationChecker->isGranted(
                PermissionMap::PERMISSION_DELETE,
                $node
            )
            && $this->nodeAdmin->hasAccess('delete')
            && (empty($node->getInternalName()) || $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN'))
        ) {
            $menu->addChild(
                'action.delete',
                [
                    'linkAttributes' => [
                        'type' => 'button',
                        'class' => 'btn btn-default btn--raise-on-hover',
                        'onClick' => 'oldEdited = isEdited; isEdited=false',
                        'data-toggle' => 'modal',
                        'data-keyboard' => 'true',
                        'data-target' => '#delete-page-modal',
                    ],
                    'extras' => ['renderType' => 'button'],
                ]
            );
        }

        $this->dispatcher->dispatch(
            Events::CONFIGURE_ACTION_MENU,
            new ConfigureActionMenuEvent(
                $this->factory,
                $menu,
                $activeNodeVersion
            )
        );

        return $menu;
    }

    /**
     * @return ItemInterface
     */
    public function createTopActionsMenu()
    {
        $menu = $this->createActionsMenu();
        $menu->setChildrenAttribute('id', 'page-main-actions-top');
        $menu->setChildrenAttribute(
            'class',
            'page-main-actions page-main-actions--top'
        );

        return $menu;
    }

    /**
     * @return ItemInterface
     */
    public function createHomeActionsMenu()
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute(
            'class',
            'page-main-actions js-auto-collapse-buttons'
        );
        $menu->addChild(
            'action.addhomepage',
            [
                'linkAttributes' => [
                    'type' => 'button',
                    'class' => 'btn btn-default btn--raise-on-hover',
                    'data-toggle' => 'modal',
                    'data-keyboard' => 'true',
                    'data-target' => '#add-homepage-modal',
                ],
                'extras' => ['renderType' => 'button'],
            ]
        );

        return $menu;
    }

    /**
     * @return ItemInterface
     */
    public function createTopHomeActionsMenu()
    {
        $menu = $this->createHomeActionsMenu();
        $menu->setChildrenAttribute('id', 'page-main-actions-top');
        $menu->setChildrenAttribute(
            'class',
            'page-main-actions page-main-actions--top'
        );

        return $menu;
    }

    /**
     * Set activeNodeVersion.
     *
     * @return ActionsMenuBuilder
     */
    public function setActiveNodeVersion(NodeVersion $activeNodeVersion)
    {
        $this->activeNodeVersion = $activeNodeVersion;

        return $this;
    }

    /**
     * Get activeNodeVersion.
     *
     * @return NodeVersion
     */
    public function getActiveNodeVersion()
    {
        return $this->activeNodeVersion;
    }

    /**
     * @param bool $value
     */
    public function setEditableNode($value)
    {
        $this->isEditableNode = $value;
    }
}
