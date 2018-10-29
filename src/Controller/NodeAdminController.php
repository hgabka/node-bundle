<?php

namespace Hgabka\NodeBundle\Controller;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Hgabka\NodeBundle\AdminList\NodeAdminListConfigurator;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Entity\QueuedNodeTranslationAction;
use Hgabka\NodeBundle\Event\AdaptFormEvent;
use Hgabka\NodeBundle\Event\CopyPageTranslationNodeEvent;
use Hgabka\NodeBundle\Event\Events;
use Hgabka\NodeBundle\Event\NodeEvent;
use Hgabka\NodeBundle\Event\RecopyPageTranslationNodeEvent;
use Hgabka\NodeBundle\Event\RevertNodeAction;
use Hgabka\NodeBundle\Form\NodeMenuTabAdminType;
use Hgabka\NodeBundle\Form\NodeMenuTabTranslationAdminType;
use Hgabka\NodeBundle\Helper\Menu\ActionsMenuBuilder;
use Hgabka\NodeBundle\Helper\NodeAdmin\NodeAdminPublisher;
use Hgabka\NodeBundle\Helper\NodeAdmin\NodeVersionLockHelper;
use Hgabka\NodeBundle\Helper\Services\ACLPermissionCreatorService;
use Hgabka\NodeBundle\Repository\NodeVersionRepository;
use Hgabka\UtilsBundle\AdminList\AdminListFactory;
use Hgabka\UtilsBundle\Entity\EntityInterface;
use Hgabka\UtilsBundle\FlashMessages\FlashTypes;
use Hgabka\UtilsBundle\Helper\ClassLookup;
use Hgabka\UtilsBundle\Helper\CloneHelper;
use Hgabka\UtilsBundle\Helper\FormWidgets\FormWidget;
use Hgabka\UtilsBundle\Helper\FormWidgets\Tabs\Tab;
use Hgabka\UtilsBundle\Helper\FormWidgets\Tabs\TabPane;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * NodeAdminController.
 */
class NodeAdminController extends CRUDController
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var BaseUser
     */
    protected $user;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param Request $request
     *
     * @return array
     */
    public function listAction()
    {
        $request = $this->getRequest();
        $this->admin->checkAccess('list');
        $preResponse = $this->preList($request);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->init($request);

        $nodeAdminListConfigurator = new NodeAdminListConfigurator(
            $this->em,
            $this->aclHelper,
            $this->locale,
            PermissionMap::PERMISSION_VIEW,
            $this->authorizationChecker,
            $this->admin
        );

        $locale = $this->locale;
        $acl = $this->authorizationChecker;
        $itemRoute = function (EntityInterface $item) use ($locale, $acl) {
            if ($acl->isGranted(PermissionMap::PERMISSION_VIEW, $item->getNode())) {
                return [
                    'path' => '_slug_preview',
                    'params' => ['_locale' => $locale, 'url' => $item->getUrl()],
                ];
            }
        };
        $nodeAdminListConfigurator->addSimpleItemAction('Preview', $itemRoute, 'eye');

        $nodeAdminListConfigurator->setShowAddHomepage($this->getParameter('hgabka_node.show_add_homepage') && $this->isGranted('ROLE_SUPER_ADMIN'));

        /** @var AdminList $adminlist */
        $adminlist = $this->get(AdminListFactory::class)->createList($nodeAdminListConfigurator);
        $adminlist->bindRequest($request);

        return $this->renderWithExtraParams('HgabkaNodeBundle:Admin:list.html.twig', [
            'adminlist' => $adminlist,
        ]);
    }

    /**
     * @Route(
     *      "/{id}/copyfromotherlanguage",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_copyfromotherlanguage"
     * )
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     * @param int     $id      The node id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function copyFromOtherLanguageAction(Request $request, $id)
    {
        $this->init($request);
        // @var Node $node
        $this->admin->checkAccess('copy');
        $node = $this->em->getRepository(Node::class)->find($id);

        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $node);

        $originalLanguage = $request->get('originallanguage');
        $otherLanguageNodeTranslation = $node->getNodeTranslation($originalLanguage, true);
        $otherLanguageNodeNodeVersion = $otherLanguageNodeTranslation->getPublicNodeVersion();
        $otherLanguagePage = $otherLanguageNodeNodeVersion->getRef($this->em);
        $myLanguagePage = $this->get(CloneHelper::class)
            ->deepCloneAndSave($otherLanguagePage);

        // @var NodeTranslation $nodeTranslation
        $nodeTranslation = $this->em->getRepository(NodeTranslation::class)
            ->createNodeTranslationFor($myLanguagePage, $this->locale, $node, $this->user);
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();

        $this->get('event_dispatcher')->dispatch(
            Events::COPY_PAGE_TRANSLATION,
            new CopyPageTranslationNodeEvent(
                $node,
                $nodeTranslation,
                $nodeVersion,
                $myLanguagePage,
                $otherLanguageNodeTranslation,
                $otherLanguageNodeNodeVersion,
                $otherLanguagePage,
                $originalLanguage
            )
        );

        return $this->redirect($this->generateUrl('HgabkaNodeBundle_nodes_edit', ['id' => $id]));
    }

    /**
     * @Route(
     *      "/{id}/recopyfromotherlanguage",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_recopyfromotherlanguage"
     * )
     * @Method("POST")
     * @Template()
     *
     * @param Request $request
     * @param int     $id      The node id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function recopyFromOtherLanguageAction(Request $request, $id)
    {
        $this->init($request);

        $this->admin->checkAccess('copy');
        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);

        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $node);

        $otherLanguageNodeTranslation = $this->em->getRepository(NodeTranslation::class)->find($request->get('source'));
        $otherLanguageNodeNodeVersion = $otherLanguageNodeTranslation->getPublicNodeVersion();
        $otherLanguagePage = $otherLanguageNodeNodeVersion->getRef($this->em);
        $myLanguagePage = $this->get(CloneHelper::class)
            ->deepCloneAndSave($otherLanguagePage);

        // @var NodeTranslation $nodeTranslation
        $nodeTranslation = $this->em->getRepository(NodeTranslation::class)
            ->addDraftNodeVersionFor($myLanguagePage, $this->locale, $node, $this->user);
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();

        $this->get('event_dispatcher')->dispatch(
            Events::RECOPY_PAGE_TRANSLATION,
            new RecopyPageTranslationNodeEvent(
                $node,
                $nodeTranslation,
                $nodeVersion,
                $myLanguagePage,
                $otherLanguageNodeTranslation,
                $otherLanguageNodeNodeVersion,
                $otherLanguagePage,
                $otherLanguageNodeTranslation->getLang()
            )
        );

        return $this->redirect($this->generateUrl('HgabkaNodeBundle_nodes_edit', ['id' => $id, 'subaction' => NodeVersion::DRAFT_VERSION]));
    }

    /**
     * @Route(
     *      "/{id}/createemptypage",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_createemptypage"
     * )
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function createEmptyPageAction(Request $request, $id)
    {
        $this->init($request);
        // @var Node $node
        $this->admin->checkAccess('create');
        $node = $this->em->getRepository(Node::class)->find($id);

        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $node);

        $entityName = $node->getRefEntityName();
        // @var HasNodeInterface $myLanguagePage
        $myLanguagePage = new $entityName();
        $myLanguagePage->setTitle('New page');

        $this->em->persist($myLanguagePage);
        $this->em->flush();
        // @var NodeTranslation $nodeTranslation
        $nodeTranslation = $this->em->getRepository(NodeTranslation::class)
            ->createNodeTranslationFor($myLanguagePage, $this->locale, $node, $this->user);
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();

        $this->get('event_dispatcher')->dispatch(
            Events::ADD_EMPTY_PAGE_TRANSLATION,
            new NodeEvent($node, $nodeTranslation, $nodeVersion, $myLanguagePage)
        );

        return $this->redirect($this->generateUrl('HgabkaNodeBundle_nodes_edit', ['id' => $id]));
    }

    /**
     * @Route("/{id}/publish", requirements={"id" =
     *                         "\d+"},
     *                         name="HgabkaNodeBundle_nodes_publish")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function publishAction(Request $request, $id)
    {
        $this->init($request);
        $this->admin->checkAccess('publish');
        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);

        $nodeTranslation = $node->getNodeTranslation($this->locale, true);
        $request = $this->get('request_stack')->getCurrentRequest();

        if ($request->get('pub_date')) {
            $date = new \DateTime(
                $request->get('pub_date').' '.$request->get('pub_time')
            );
            $this->get(NodeAdminPublisher::class)->publishLater(
                $nodeTranslation,
                $date
            );
            $this->addFlash(
                FlashTypes::SUCCESS,
                $this->get('translator')->trans('hg_node.admin.publish.flash.success_scheduled')
            );
        } else {
            $this->get(NodeAdminPublisher::class)->publish(
                $nodeTranslation
            );
            $this->addFlash(
                FlashTypes::SUCCESS,
                $this->get('translator')->trans('hg_node.admin.publish.flash.success_published')
            );
        }

        return $this->redirect($this->generateUrl('HgabkaNodeBundle_nodes_edit', ['id' => $node->getId()]));
    }

    /**
     * @Route(
     *      "/{id}/unpublish",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_unpublish"
     * )
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function unPublishAction(Request $request, $id)
    {
        $this->init($request);
        $this->admin->checkAccess('unpublish');
        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);

        $nodeTranslation = $node->getNodeTranslation($this->locale, true);
        $request = $this->get('request_stack')->getCurrentRequest();

        if ($request->get('unpub_date')) {
            $date = new \DateTime($request->get('unpub_date').' '.$request->get('unpub_time'));
            $this->get(NodeAdminPublisher::class)->unPublishLater($nodeTranslation, $date);
            $this->addFlash(
                FlashTypes::SUCCESS,
                $this->get('translator')->trans('hg_node.admin.unpublish.flash.success_scheduled')
            );
        } else {
            $this->get(NodeAdminPublisher::class)->unPublish($nodeTranslation);
            $this->addFlash(
                FlashTypes::SUCCESS,
                $this->get('translator')->trans('hg_node.admin.unpublish.flash.success_unpublished')
            );
        }

        return $this->redirect($this->generateUrl('HgabkaNodeBundle_nodes_edit', ['id' => $node->getId()]));
    }

    /**
     * @Route(
     *      "/{id}/unschedulepublish",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_unschedule_publish"
     * )
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function unSchedulePublishAction(Request $request, $id)
    {
        $this->init($request);
        $this->admin->checkAccess('publish');

        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);

        $nodeTranslation = $node->getNodeTranslation($this->locale, true);
        $this->get(NodeAdminPublisher::class)->unSchedulePublish($nodeTranslation);

        $this->addFlash(
            FlashTypes::SUCCESS,
            $this->get('translator')->trans('hg_node.admin.unschedule.flash.success')
        );

        return $this->redirect($this->generateUrl('HgabkaNodeBundle_nodes_edit', ['id' => $id]));
    }

    /**
     * @Route(
     *      "/{id}/delete",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_delete"
     * )
     * @Template()
     * @Method("POST")
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function deleteAction($id)
    {
        $request = $this->getRequest();
        $this->admin->checkAccess('delete');

        $this->init($request);
        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);

        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_DELETE, $node);

        $nodeTranslation = $node->getNodeTranslation($this->locale, true);
        $nodeVersion = $nodeTranslation->getPublicNodeVersion();
        $page = $nodeVersion->getRef($this->em);

        $this->get('event_dispatcher')->dispatch(
            Events::PRE_DELETE,
            new NodeEvent($node, $nodeTranslation, $nodeVersion, $page)
        );

        $node->setDeleted(true);
        $this->em->persist($node);

        $children = $node->getChildren();
        $this->deleteNodeChildren($this->em, $this->user, $this->locale, $children);
        $this->em->flush();

        $event = new NodeEvent($node, $nodeTranslation, $nodeVersion, $page);
        $this->get('event_dispatcher')->dispatch(Events::POST_DELETE, $event);
        if (null === $response = $event->getResponse()) {
            $nodeParent = $node->getParent();
            // Check if we have a parent. Otherwise redirect to pages overview.
            if ($nodeParent) {
                $url = $this->get('router')->generate(
                    'HgabkaNodeBundle_nodes_edit',
                    ['id' => $nodeParent->getId()]
                );
            } else {
                $url = $this->admin->generateUrl('list');
            }
            $response = new RedirectResponse($url);
        }

        $this->addFlash(
            FlashTypes::SUCCESS,
            $this->get('translator')->trans('hg_node.admin.delete.flash.success')
        );

        return $response;
    }

    /**
     * @Route(
     *      "/{id}/duplicate",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_duplicate"
     * )
     * @Method("POST")
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function duplicateAction(Request $request, $id)
    {
        $this->init($request);
        $this->admin->checkAccess('duplicate');

        // @var Node $parentNode
        $originalNode = $this->em->getRepository(Node::class)
            ->find($id);

        // Check with Acl
        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $originalNode);

        $request = $this->get('request_stack')->getCurrentRequest();

        $originalNodeTranslations = $originalNode->getNodeTranslation($this->locale, true);
        $originalRef = $originalNodeTranslations->getPublicNodeVersion()->getRef($this->em);
        $newPage = $this->get(CloneHelper::class)
            ->deepCloneAndSave($originalRef);

        //set the title
        $title = $request->get('title');
        if (\is_string($title) && !empty($title)) {
            $newPage->setTitle($title);
        } else {
            $newPage->setTitle('New page');
        }

        //set the parent
        $parentNodeTranslation = $originalNode->getParent()->getNodeTranslation($this->locale, true);
        $parent = $parentNodeTranslation->getPublicNodeVersion()->getRef($this->em);
        $newPage->setParent($parent);
        $this->em->persist($newPage);
        $this->em->flush();

        // @var Node $nodeNewPage
        $nodeNewPage = $this->em->getRepository(Node::class)->createNodeFor(
            $newPage,
            $this->locale,
            $this->user
        );

        $nodeTranslation = $nodeNewPage->getNodeTranslation($this->locale, true);
        if ($newPage->isStructureNode()) {
            $nodeTranslation->setSlug('');
            $this->em->persist($nodeTranslation);
        }
        $this->em->flush();

        $this->updateAcl($originalNode, $nodeNewPage);

        $this->addFlash(
            FlashTypes::SUCCESS,
            $this->get('translator')->trans('hg_node.admin.duplicate.flash.success')
        );

        return $this->redirect(
            $this->generateUrl('HgabkaNodeBundle_nodes_edit', ['id' => $nodeNewPage->getId()])
        );
    }

    /**
     * @Route(
     *      "/{id}/revert",
     *      requirements={"id" = "\d+"},
     *      defaults={"subaction" = "public"},
     *      name="HgabkaNodeBundle_nodes_revert"
     * )
     * @Template()
     * @Method("GET")
     *
     * @param Request $request
     * @param int     $id      The node id
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     *
     * @return RedirectResponse
     */
    public function revertAction(Request $request, $id)
    {
        $this->init($request);
        $this->admin->checkAccess('revert');

        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);

        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $node);

        $version = $request->get('version');

        if (empty($version) || !is_numeric($version)) {
            throw new InvalidArgumentException('No version was specified');
        }

        // @var NodeVersionRepository $nodeVersionRepo
        $nodeVersionRepo = $this->em->getRepository(NodeVersion::class);
        // @var NodeVersion $nodeVersion
        $nodeVersion = $nodeVersionRepo->find($version);

        if (null === $nodeVersion) {
            throw new InvalidArgumentException('Version does not exist');
        }

        // @var NodeTranslation $nodeTranslation
        $nodeTranslation = $node->getNodeTranslation($this->locale, true);
        $page = $nodeVersion->getRef($this->em);
        // @var HasNodeInterface $clonedPage
        $clonedPage = $this->get(CloneHelper::class)
            ->deepCloneAndSave($page);
        $newNodeVersion = $nodeVersionRepo->createNodeVersionFor(
            $clonedPage,
            $nodeTranslation,
            $this->user,
            $nodeVersion,
            'draft'
        );

        $nodeTranslation->setTitle($clonedPage->getTitle());
        $this->em->persist($nodeTranslation);
        $this->em->flush();

        $this->get('event_dispatcher')->dispatch(
            Events::REVERT,
            new RevertNodeAction(
                $node,
                $nodeTranslation,
                $newNodeVersion,
                $clonedPage,
                $nodeVersion,
                $page
            )
        );

        $this->addFlash(
            FlashTypes::SUCCESS,
            $this->get('translator')->trans('hg_node.admin.revert.flash.success')
        );

        return $this->redirect(
            $this->generateUrl(
                'HgabkaNodeBundle_nodes_edit',
                [
                    'id' => $id,
                    'subaction' => 'draft',
                ]
            )
        );
    }

    /**
     * @Route(
     *      "/{id}/add",
     *      requirements={"id" = "\d+"},
     *      name="HgabkaNodeBundle_nodes_add"
     * )
     * @Method("POST")
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     *
     * @return RedirectResponse
     */
    public function addAction(Request $request, $id)
    {
        $this->init($request);
        $this->admin->checkAccess('create');

        // @var Node $parentNode
        $parentNode = $this->em->getRepository(Node::class)->find($id);

        // Check with Acl
        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $parentNode);

        $parentNodeTranslation = $parentNode->getNodeTranslation($this->locale, true);
        $parentNodeVersion = $parentNodeTranslation->getPublicNodeVersion();
        $parentPage = $parentNodeVersion->getRef($this->em);

        $type = $this->validatePageType($request);
        $newPage = $this->createNewPage($request, $type);
        $newPage->setParent($parentPage);

        // @var Node $nodeNewPage
        $nodeNewPage = $this->em->getRepository(Node::class)
            ->createNodeFor($newPage, $this->locale, $this->user);
        $nodeTranslation = $nodeNewPage->getNodeTranslation(
            $this->locale,
            true
        );
        $weight = $this->em->getRepository(NodeTranslation::class)
                ->getMaxChildrenWeight($parentNode, $this->locale) + 1;
        $nodeTranslation->setWeight($weight);

        if ($newPage->isStructureNode()) {
            $nodeTranslation->setSlug('');
        }

        $this->em->persist($nodeTranslation);
        $this->em->flush();

        $this->updateAcl($parentNode, $nodeNewPage);

        $nodeVersion = $nodeTranslation->getPublicNodeVersion();

        $this->get('event_dispatcher')->dispatch(
            Events::ADD_NODE,
            new NodeEvent(
                $nodeNewPage,
                $nodeTranslation,
                $nodeVersion,
                $newPage
            )
        );

        return $this->redirect(
            $this->generateUrl(
                'HgabkaNodeBundle_nodes_edit',
                ['id' => $nodeNewPage->getId()]
            )
        );
    }

    /**
     * @Route("/add-homepage", name="HgabkaNodeBundle_nodes_add_homepage")
     * @Method("POST")
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     *
     * @return RedirectResponse
     */
    public function addHomepageAction(Request $request)
    {
        $this->init($request);

        // Check with Acl
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $type = $this->validatePageType($request);

        $newPage = $this->createNewPage($request, $type);

        // @var Node $nodeNewPage
        $nodeNewPage = $this->em->getRepository(Node::class)
            ->createNodeFor($newPage, $this->locale, $this->user);
        $nodeTranslation = $nodeNewPage->getNodeTranslation(
            $this->locale,
            true
        );
        $this->em->flush();

        // Set default permissions
        $this->get(ACLPermissionCreatorService::class)
            ->createPermission($nodeNewPage);

        $nodeVersion = $nodeTranslation->getPublicNodeVersion();

        $this->get('event_dispatcher')->dispatch(
            Events::ADD_NODE,
            new NodeEvent(
                $nodeNewPage,
                $nodeTranslation,
                $nodeVersion,
                $newPage
            )
        );

        return $this->redirect(
            $this->generateUrl(
                'HgabkaNodeBundle_nodes_edit',
                ['id' => $nodeNewPage->getId()]
            )
        );
    }

    /**
     * @Route("/reorder", name="HgabkaNodeBundle_nodes_reorder")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @throws AccessDeniedException
     *
     * @return string
     */
    public function reorderAction(Request $request)
    {
        $this->init($request);
        $this->admin->checkAccess('reorder');
        $nodes = [];
        $nodeIds = $request->get('nodes');
        $changeParents = $request->get('parent');

        foreach ($nodeIds as $id) {
            // @var Node $node
            $node = $this->em->getRepository(Node::class)->find($id);
            $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $node);
            $nodes[] = $node;
        }

        $weight = 0;
        foreach ($nodes as $node) {
            $newParentId = isset($changeParents[$node->getId()]) ? $changeParents[$node->getId()] : null;
            if ($newParentId) {
                $parent = $this->em->getRepository(Node::class)->find($newParentId);
                $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $parent);
                $node->setParent($parent);
                $this->em->persist($node);
                $this->em->flush($node);
            }

            // @var NodeTranslation $nodeTranslation
            $nodeTranslation = $node->getNodeTranslation($this->locale, true);

            if ($nodeTranslation) {
                $nodeVersion = $nodeTranslation->getPublicNodeVersion();
                $page = $nodeVersion->getRef($this->em);

                $this->get('event_dispatcher')->dispatch(
                    Events::PRE_PERSIST,
                    new NodeEvent($node, $nodeTranslation, $nodeVersion, $page)
                );

                $nodeTranslation->setWeight($weight);
                $this->em->persist($nodeTranslation);
                $this->em->flush($nodeTranslation);

                $this->get('event_dispatcher')->dispatch(
                    Events::POST_PERSIST,
                    new NodeEvent($node, $nodeTranslation, $nodeVersion, $page)
                );

                ++$weight;
            }
        }

        return new JsonResponse(
            [
                'Success' => 'The node-translations for ['.$this->locale.'] have got new weight values',
            ]
        );
    }

    public function editAction($id = null)
    {
        return $this->redirectToRoute('HgabkaNodeBundle_nodes_edit', ['id' => $id]);
    }

    /**
     * @Route(
     *      "/{id}/{subaction}",
     *      requirements={"id" = "\d+"},
     *      defaults={"subaction" = "public"},
     *      name="HgabkaNodeBundle_nodes_edit"
     * )
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param int     $id        The node id
     * @param string  $subaction The subaction (draft|public)
     *
     * @throws AccessDeniedException
     *
     * @return array|RedirectResponse
     */
    public function editCustomAction($id, $subaction)
    {
        $request = $this->getRequest();

        $this->init($request);

        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);
        $this->admin->checkAccess('edit', $node);
        $preResponse = $this->preEdit($request, $node);

        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($node);
        $this->denyAccessUnlessGranted(PermissionMap::PERMISSION_EDIT, $node);

        $tabPane = new TabPane(
            'todo',
            $request,
            $this->container->get('form.factory')
        );

        $nodeTranslation = $node->getNodeTranslation($this->locale, true);
        if (!$nodeTranslation) {
            return $this->renderNodeNotTranslatedPage($node);
        }

        $nodeVersion = $nodeTranslation->getPublicNodeVersion();
        $draftNodeVersion = $nodeTranslation->getDraftNodeVersion();
        $nodeVersionIsLocked = false;

        // @var HasNodeInterface $page
        $page = null;
        $draft = ('draft' === $subaction);
        $saveAsDraft = $request->get('saveasdraft');
        if ((!$draft && !empty($saveAsDraft)) || ($draft && null === $draftNodeVersion)) {
            // Create a new draft version
            $draft = true;
            $subaction = 'draft';
            $page = $nodeVersion->getRef($this->em);
            $nodeVersion = $this->createDraftVersion(
                $page,
                $nodeTranslation,
                $nodeVersion
            );
            $draftNodeVersion = $nodeVersion;
        } elseif ($draft) {
            $nodeVersion = $draftNodeVersion;
            $page = $nodeVersion->getRef($this->em);
        } else {
            if ('POST' === $request->getMethod()) {
                $nodeVersionIsLocked = $this->isNodeVersionLocked($nodeTranslation, true);

                //Check the version timeout and make a new nodeversion if the timeout is passed
                $thresholdDate = date(
                    'Y-m-d H:i:s',
                    time() - $this->getParameter(
                        'hgabka_node.version_timeout'
                    )
                );
                $updatedDate = date(
                    'Y-m-d H:i:s',
                    strtotime($nodeVersion->getUpdated()->format('Y-m-d H:i:s'))
                );
                if ($thresholdDate >= $updatedDate || $nodeVersionIsLocked) {
                    $page = $nodeVersion->getRef($this->em);
                    if ($nodeVersion === $nodeTranslation->getPublicNodeVersion()) {
                        $this->get(NodeAdminPublisher::class)
                            ->createPublicVersion(
                                $page,
                                $nodeTranslation,
                                $nodeVersion,
                                $this->user
                            );
                    } else {
                        $this->createDraftVersion(
                            $page,
                            $nodeTranslation,
                            $nodeVersion
                        );
                    }
                }
            }
            $page = $nodeVersion->getRef($this->em);
        }
        $isStructureNode = $page->isStructureNode();

        $menubuilder = $this->get(ActionsMenuBuilder::class);
        $menubuilder->setActiveNodeVersion($nodeVersion);
        $menubuilder->setEditableNode(!$isStructureNode);

        // Building the form
        $propertiesWidget = new FormWidget();

        $propertiesWidget->addType('main', $page->getDefaultAdminType(), $page);
        $propertiesWidget->addType('node', $node->getDefaultAdminType(), $node);

        $tabPane->addTab(new Tab('hg_node.tab.properties.title', $propertiesWidget));

        // Menu tab
        $menuWidget = new FormWidget();
        $menuWidget->addType(
            'menunodetranslation',
            NodeMenuTabTranslationAdminType::class,
            $nodeTranslation,
            ['slugable' => !$isStructureNode]
        );
        $menuWidget->addType('menunode', NodeMenuTabAdminType::class, $node, ['available_in_nav' => !$isStructureNode]);
        $tabPane->addTab(new Tab('hg_node.tab.menu.title', $menuWidget));

        $this->get('event_dispatcher')->dispatch(
            Events::ADAPT_FORM,
            new AdaptFormEvent(
                $request,
                $tabPane,
                $page,
                $node,
                $nodeTranslation,
                $nodeVersion
            )
        );

        $tabPane->buildForm();

        if ('POST' === $request->getMethod()) {
            $tabPane->bindRequest($request);

            // Don't redirect to listing when coming from ajax request, needed for url chooser.
            if ($tabPane->isValid() && !$request->isXmlHttpRequest()) {
                $this->get('event_dispatcher')->dispatch(
                    Events::PRE_PERSIST,
                    new NodeEvent($node, $nodeTranslation, $nodeVersion, $page)
                );

                $nodeTranslation->setTitle($page->getTitle());
                if ($isStructureNode) {
                    $nodeTranslation->setSlug('');
                }
                $nodeVersion->setUpdated(new DateTime());
                if ('public' === $nodeVersion->getType()) {
                    $nodeTranslation->setUpdated($nodeVersion->getUpdated());
                }
                $this->em->persist($nodeTranslation);
                $this->em->persist($nodeVersion);
                $tabPane->persist($this->em);
                $this->em->flush();

                $this->get('event_dispatcher')->dispatch(
                    Events::POST_PERSIST,
                    new NodeEvent($node, $nodeTranslation, $nodeVersion, $page)
                );

                if ($nodeVersionIsLocked) {
                    $this->addFlash(
                        FlashTypes::SUCCESS,
                        $this->get('translator')->trans('hg_node.admin.edit.flash.locked_success')
                    );
                } else {
                    $this->addFlash(
                        FlashTypes::SUCCESS,
                        $this->get('translator')->trans('hg_node.admin.edit.flash.success')
                    );
                }

                $params = [
                    'id' => $node->getId(),
                    'subaction' => $subaction,
                    'currenttab' => $tabPane->getActiveTab(),
                ];
                $params = array_merge(
                    $params,
                    $tabPane->getExtraParams($request)
                );

                return $this->redirect(
                    $this->generateUrl(
                        'HgabkaNodeBundle_nodes_edit',
                        $params
                    )
                );
            }
        }

        $nodeVersions = $this->em->getRepository(
            NodeVersion::class
        )->findBy(
            ['nodeTranslation' => $nodeTranslation],
            ['updated' => 'ASC']
        );
        $queuedNodeTranslationAction = $this->em->getRepository(
            QueuedNodeTranslationAction::class
        )->findOneBy(['nodeTranslation' => $nodeTranslation]);

        $params = [
            'page' => $page,
            'entityname' => ClassLookup::getClass($page),
            'nodeVersions' => $nodeVersions,
            'node' => $node,
            'nodeTranslation' => $nodeTranslation,
            'draft' => $draft,
            'draftNodeVersion' => $draftNodeVersion,
            'nodeVersion' => $nodeVersion,
            'subaction' => $subaction,
            'tabPane' => $tabPane,
            'editmode' => true,
            'queuedNodeTranslationAction' => $queuedNodeTranslationAction,
            'nodeVersionLockCheck' => $this->container->getParameter('hgabka_node.lock_enabled'),
            'nodeVersionLockInterval' => $this->container->getParameter('hgabka_node.lock_check_interval'),
        ];

        return $this->renderWithExtraParams('@HgabkaNode/NodeAdmin/edit'.($request->isXmlHttpRequest() ? 'Ajax' : '').'.html.twig', $params);
    }

    /**
     * @Route(
     *      "checkNodeVersionLock/{id}/{public}",
     *      requirements={"id" = "\d+", "public" = "(0|1)"},
     *      name="HgabkaNodeBundle_nodes_versionlock_check"
     * )
     *
     * @param Request $request
     * @param $id
     * @param mixed $public
     *
     * @return JsonResponse
     */
    public function checkNodeVersionLockAction(Request $request, $id, $public)
    {
        $nodeVersionIsLocked = false;
        $message = '';
        $this->init($request);
        $this->admin->checkAccess('edit');
        // @var Node $node
        $node = $this->em->getRepository(Node::class)->find($id);

        try {
            $this->checkPermission($node, PermissionMap::PERMISSION_EDIT);

            /** @var NodeVersionLockHelper $nodeVersionLockHelper */
            $nodeVersionLockHelper = $this->get('kunstmaan_node.admin_node.node_version_lock_helper');
            $nodeTranslation = $node->getNodeTranslation($this->locale, true);

            if ($nodeTranslation) {
                $nodeVersionIsLocked = $nodeVersionLockHelper->isNodeVersionLocked($this->getUser(), $nodeTranslation, $public);

                if ($nodeVersionIsLocked) {
                    $users = $nodeVersionLockHelper->getUsersWithNodeVersionLock($nodeTranslation, $public, $this->getUser());
                    $message = $this->get('translator')->trans('hg_node.admin.edit.flash.locked', ['%users%' => implode(', ', $users)]);
                }
            }
        } catch (AccessDeniedException $ade) {
        }

        return new JsonResponse(['lock' => $nodeVersionIsLocked, 'message' => $message]);
    }

    protected function configure()
    {
        $this->getRequest()->attributes->set('_sonata_admin', 'hgabka_node.admin.node');
        parent::configure();
    }

    /**
     * init.
     *
     * @param Request $request
     */
    protected function init(Request $request)
    {
        $this->em = $this->getDoctrine()->getManager();
        $this->locale = $request->getLocale();
        $this->authorizationChecker = $this->get('security.authorization_checker');
        $this->user = $this->getUser();
        $this->aclHelper = $this->get(AclHelper::class);
    }

    /**
     * @param NodeTranslation $nodeTranslation
     * @param bool            $isPublic
     *
     * @return bool
     */
    private function isNodeVersionLocked(NodeTranslation $nodeTranslation, $isPublic)
    {
        if ($this->container->getParameter('hgabka_node.lock_enabled')) {
            /** @var NodeVersionLockHelper $nodeVersionLockHelper */
            $nodeVersionLockHelper = $this->get('hgabka_node.admin_node.node_version_lock_helper');
            $nodeVersionIsLocked = $nodeVersionLockHelper->isNodeVersionLocked($this->getUser(), $nodeTranslation, $isPublic);

            return $nodeVersionIsLocked;
        }

        return false;
    }

    /**
     * @param HasNodeInterface $page            The page
     * @param NodeTranslation  $nodeTranslation The node translation
     * @param NodeVersion      $nodeVersion     The node version
     *
     * @return NodeVersion
     */
    private function createDraftVersion(
        HasNodeInterface $page,
        NodeTranslation $nodeTranslation,
        NodeVersion $nodeVersion
    ) {
        $publicPage = $this->get(CloneHelper::class)
            ->deepCloneAndSave($page);
        // @var NodeVersion $publicNodeVersion

        $publicNodeVersion = $this->em->getRepository(
            NodeVersion::class
        )->createNodeVersionFor(
            $publicPage,
            $nodeTranslation,
            $this->user,
            $nodeVersion->getOrigin(),
            'public',
            $nodeVersion->getCreated()
        );

        $nodeTranslation->setPublicNodeVersion($publicNodeVersion);
        $nodeVersion->setType('draft');
        $nodeVersion->setOrigin($publicNodeVersion);
        $nodeVersion->setCreated(new DateTime());

        $this->em->persist($nodeTranslation);
        $this->em->persist($nodeVersion);
        $this->em->flush();

        $this->get('event_dispatcher')->dispatch(
            Events::CREATE_DRAFT_VERSION,
            new NodeEvent(
                $nodeTranslation->getNode(),
                $nodeTranslation,
                $nodeVersion,
                $page
            )
        );

        return $nodeVersion;
    }

    /**
     * @param Node   $node       The node
     * @param string $permission The permission to check for
     *
     * @throws AccessDeniedException
     */
    private function checkPermission(Node $node, $permission)
    {
        if (false === $this->authorizationChecker->isGranted($permission, $node)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param EntityManager   $em       The Entity Manager
     * @param BaseUser        $user     The user who deletes the children
     * @param string          $locale   The locale that was used
     * @param ArrayCollection $children The children array
     */
    private function deleteNodeChildren(
        EntityManager $em,
        $user,
        $locale,
        ArrayCollection $children
    ) {
        // @var Node $childNode
        foreach ($children as $childNode) {
            $childNodeTranslation = $childNode->getNodeTranslation(
                $this->locale,
                true
            );

            $childNodeVersion = $childNodeTranslation->getPublicNodeVersion();
            $childNodePage = $childNodeVersion->getRef($this->em);

            $this->get('event_dispatcher')->dispatch(
                Events::PRE_DELETE,
                new NodeEvent(
                    $childNode,
                    $childNodeTranslation,
                    $childNodeVersion,
                    $childNodePage
                )
            );

            $childNode->setDeleted(true);
            $this->em->persist($childNode);

            $children2 = $childNode->getChildren();
            $this->deleteNodeChildren($em, $user, $locale, $children2);

            $this->get('event_dispatcher')->dispatch(
                Events::POST_DELETE,
                new NodeEvent(
                    $childNode,
                    $childNodeTranslation,
                    $childNodeVersion,
                    $childNodePage
                )
            );
        }
    }

    /**
     * @param $originalNode
     * @param $nodeNewPage
     */
    private function updateAcl($originalNode, $nodeNewPage)
    {
        $this->container->get(AclHelper::class)->updateAcl($originalNode, $nodeNewPage);
    }

    /**
     * @param Request $request
     * @param string  $type
     *
     * @return HasNodeInterface
     */
    private function createNewPage(Request $request, $type)
    {
        // @var HasNodeInterface $newPage
        $newPage = new $type();

        $title = $request->get('title');
        if (\is_string($title) && !empty($title)) {
            $newPage->setTitle($title);
        } else {
            $newPage->setTitle($this->get('translator')->trans('hg_node.admin.new_page.title.default'));
        }
        $this->em->persist($newPage);
        $this->em->flush();

        return $newPage;
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throw InvalidArgumentException
     */
    private function validatePageType($request)
    {
        $type = $request->get('type');

        if (empty($type)) {
            throw new InvalidArgumentException(
                'Please specify a type of page you want to create'
            );
        }

        return $type;
    }

    /**
     * @param Node $node
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function renderNodeNotTranslatedPage(Node $node)
    {
        //try to find a parent node with the correct translation, if there is none allow copy.
        //if there is a parent but it doesn't have the language to copy to don't allow it
        $parentNode = $node->getParent();
        if ($parentNode) {
            $parentNodeTranslation = $parentNode->getNodeTranslation(
                $this->locale,
                true
            );
            $parentsAreOk = false;

            if ($parentNodeTranslation) {
                $parentsAreOk = $this->em->getRepository(
                    NodeTranslation::class
                )->hasParentNodeTranslationsForLanguage(
                    $node->getParent()->getNodeTranslation(
                        $this->locale,
                        true
                    ),
                    $this->locale
                );
            }
        } else {
            $parentsAreOk = true;
        }

        return $this->render(
            '@HgabkaNode/NodeAdmin/pagenottranslated.html.twig',
            [
                'node' => $node,
                'nodeTranslations' => $node->getNodeTranslations(
                    true
                ),
                'copyfromotherlanguages' => $parentsAreOk,
            ]
        );
    }
}
