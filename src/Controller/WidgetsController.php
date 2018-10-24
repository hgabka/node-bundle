<?php

namespace Hgabka\NodeBundle\Controller;

use Doctrine\ORM\EntityManager;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\StructureNode;
use Hgabka\NodeBundle\Helper\Menu\SimpleTreeView;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * WidgetsController.
 */
class WidgetsController extends Controller
{
    /**
     * @Route("/ckselecturl", name="KunstmaanNodeBundle_ckselecturl")
     * @Template("KunstmaanNodeBundle:Widgets:selectLink.html.twig")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function ckSelectLinkAction(Request $request)
    {
        $params = $this->getTemplateParameters($request);
        $params['cke'] = true;
        $params['multilanguage'] = $this->getParameter('multilanguage');

        return $params;
    }

    /**
     * Select a link.
     *
     * @Route   ("/selecturl", name="KunstmaanNodeBundle_selecturl")
     * @Template("KunstmaanNodeBundle:Widgets:selectLink.html.twig")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function selectLinkAction(Request $request)
    {
        $params = $this->getTemplateParameters($request);
        $params['cke'] = false;
        $params['multilanguage'] = $this->getParameter('multilanguage');

        return $params;
    }

    /**
     * Get the parameters needed in the template. This is common for the
     * default link chooser and the cke link chooser.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    private function getTemplateParameters(Request $request)
    {
        // @var EntityManager $em
        $em = $this->getDoctrine()->getManager();
        $locale = $request->getLocale();

        $result = $em->getRepository(Node::class)
            ->getAllMenuNodes(
                $locale,
                PermissionMap::PERMISSION_VIEW,
                $this->get('hgabka_utils.acl.native.helper'),
                true,
                $this->get('hgabka_utils.domain_configuration')->getRootNode()
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

        if (array_key_exists('HgabkaMediaBundle', $allBundles)) {
            $params = ['linkChooser' => 1];
            $cKEditorFuncNum = $request->get('CKEditorFuncNum');
            if (!empty($cKEditorFuncNum)) {
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
        ];
    }

    /**
     * Determine if current node is a structure node.
     *
     * @param string $refEntityName
     *
     * @return bool
     */
    private function isStructureNode($refEntityName)
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
