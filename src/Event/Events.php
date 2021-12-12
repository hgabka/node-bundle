<?php

namespace Hgabka\NodeBundle\Event;

/**
 * Events.
 */
class Events
{
    /**
     * The addNode event occurs for a given node, after it's being created.
     *
     * @var string
     */
    public const ADD_NODE = 'hgabka_node.addNode';

    /**
     * The addNode event occurs for a given node, after it's being reverted to a previous version.
     *
     * @var string
     */
    public const REVERT = 'hgabka_node.onRevert';

    /**
     * The preUnPublish event occurs for a given node, before it's unpublished.
     *
     * @var string
     */
    public const PRE_UNPUBLISH = 'hgabka_node.preUnPublish';

    /**
     * The postUnPublish event occurs for a given node, after it's unpublished.
     *
     * @var string
     */
    public const POST_UNPUBLISH = 'hgabka_node.postUnPublish';

    /**
     * The prePublish event occurs for a given node, before it's published.
     *
     * @var string
     */
    public const PRE_PUBLISH = 'hgabka_node.prePublish';

    /**
     * The postPublish event occurs for a given node, after it's published.
     *
     * @var string
     */
    public const POST_PUBLISH = 'hgabka_node.postPublish';

    /**
     * The preDelete event occurs for a given node, before it's deleted.
     *
     * @var string
     */
    public const PRE_DELETE = 'hgabka_node.preDelete';

    /**
     * The postDelete event occurs for a given node, after it's deleted.
     *
     * @var string
     */
    public const POST_DELETE = 'hgabka_node.postDelete';

    /**
     * The adaptForm event occurs when building the form for the node.
     *
     * @var string
     */
    public const ADAPT_FORM = 'hgabka_node.adaptForm';

    /**
     * The prePersist event occurs for a given node, before the node is persisted.
     *
     * @var string
     */
    public const PRE_PERSIST = 'hgabka_node.prePersist';

    /**
     * The postPersist event occurs for a given node, after the node is persisted.
     *
     * @var string
     */
    public const POST_PERSIST = 'hgabka_node.postPersist';

    /**
     * The createPublicVersion event occurs for a given node, when a public version is created.
     *
     * @var string
     */
    public const CREATE_PUBLIC_VERSION = 'hgabka_node.createPublicVersion';

    /**
     * The createDraftVersion event occurs for a given node, when a draft version is created.
     *
     * @var string
     */
    public const CREATE_DRAFT_VERSION = 'hgabka_node.createDraftVersion';

    /**
     * The copyPageTranslation event occurs for a given node, after a page translation has been copied.
     *
     * @var string
     */
    public const COPY_PAGE_TRANSLATION = 'hgabka_node.copyPageTranslation';

    /**
     * The recopyPageTranslation event occurs for a given node, when a recopy from a language as being asked.
     *
     * @var string
     */
    public const RECOPY_PAGE_TRANSLATION = 'hgabka_node.recopyPageTranslation';

    /**
     * The emptyPageTranslation event occurs for a given node, after a new page translation is created.
     *
     * @var string
     */
    public const ADD_EMPTY_PAGE_TRANSLATION = 'hgabka_node.emptyPageTranslation';

    /**
     * This event will be triggered when creating the menu for the page sub actions.
     * It is possible to change this menu using this event.
     *
     * @var string
     */
    public const CONFIGURE_SUB_ACTION_MENU = 'hgabka_node.configureSubActionMenu';

    /**
     * This event will be triggered when creating the menu for the page actions.
     * It is possible to change this menu using this event.
     *
     * @var string
     */
    public const CONFIGURE_ACTION_MENU = 'hgabka_node.configureActionMenu';

    /**
     * This event will be triggered when the sluglistener needs to do security checks.
     *
     * @var string
     */
    public const SLUG_SECURITY = 'hgabka_node.slug.security';

    /**
     * This event will be triggered before the slugaction is performed.
     *
     * @var string
     */
    public const PRE_SLUG_ACTION = 'hgabka_node.preSlugAction';

    /**
     * This event will be triggered after the slugaction is performed.
     *
     * @var string
     */
    public const POST_SLUG_ACTION = 'hgabka_node.postSlugAction';
}
