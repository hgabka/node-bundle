<?php

namespace Hgabka\NodeBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\StructureNode;
use Hgabka\NodeBundle\Helper\Menu\SimpleTreeView;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclNativeHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * WidgetsController.
 */
class WidgetsController extends AbstractController
{
    /**
     * WidgetsController constructor.
     */
    public function __construct(
        protected readonly ParameterBagInterface $params,
        protected readonly ManagerRegistry $doctrine,
        protected readonly AclNativeHelper $aclHelper,
        protected readonly HgabkaUtils $hgabkaUtils
    ) {
    }

    public function getAclHelper(): AclNativeHelper
    {
        return $this->aclHelper;
    }

    #[Route('/ckselecturl', name: 'HgabkaNodeBundle_ckselecturl')]
    public function ckSelectLinkAction(Request $request): Response
    {
        $params = $this->getTemplateParameters($request);
        $params['cke'] = true;
        $params['multilanguage'] = \count($this->hgabkaUtils->getAvailableLocales()) > 0;

        return $this->render('@HgabkaNode/Widgets/selectLink.html.twig', $params);
    }

    #[Route('/selecturl', name: 'HgabkaNodeBundle_selecturl')]
    public function selectLinkAction(Request $request): Response
    {
        $params = $this->getTemplateParameters($request);
        $params['cke'] = false;
        $params['multilanguage'] = \count($this->hgabkaUtils->getAvailableLocales()) > 0;

        return $this->render('@HgabkaNode/Widgets/selectLink.html.twig', $params);
    }

    protected function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        return $this->params->get($name);
    }

    protected function getBaseTemplate(): string
    {
        return $this->getParameter('sonata.admin.configuration.templates')['layout'];
    }

    /**
     * Get the parameters needed in the template. This is common for the
     * default link chooser and the cke link chooser.
     *
     * @return array
     */
    private function getTemplateParameters(Request $request): array
    {
        // @var EntityManager $em
        $em = $this->doctrine->getManager();
        $locale = $request->attributes->get('nodeLocale');

        $result = $em->getRepository(Node::class)
            ->getAllMenuNodes(
                $locale,
                PermissionMap::PERMISSION_VIEW,
                $this->getAclHelper(),
                true,
                null
            );

        $simpleTreeView = new SimpleTreeView();
        foreach ($result as $data) {
            if ($this->isStructureNode($data['ref_entity_name'])) {
                $data['online'] = true;
            }
            $simpleTreeView->addItem($data['parent'], $data);
        }

        // When the media bundle is available, we show a link in the header to the media chooser
        $allBundles = $this->getParameter('kernel.bundles');
        $mediaChooserLink = null;

        if (\array_key_exists('HgabkaMediaBundle', $allBundles)) {
            $params = ['linkChooser' => 1];
            $cKEditorFuncNum = $request->get('CKEditorFuncNum');
            if (null !== $cKEditorFuncNum) {
                $params['CKEditorFuncNum'] = $cKEditorFuncNum;
            }
            $mediaChooserLink = $this->generateUrl(
                'HgabkaMediaBundle_chooser',
                $params
            );
        }

        return [
            'tree' => $simpleTreeView,
            'mediaChooserLink' => $mediaChooserLink,
            'base_template' => $this->getBaseTemplate(),
        ];
    }

    /**
     * Determine if current node is a structure node.
     *
     * @param string $refEntityName
     *
     * @return bool
     */
    private function isStructureNode($refEntityName): bool
    {
        $structureNode = false;
        if (class_exists($refEntityName)) {
            $page = new $refEntityName();
            $structureNode = ($page instanceof StructureNode);
            unset($page);
        }

        return $structureNode;
    }
}
