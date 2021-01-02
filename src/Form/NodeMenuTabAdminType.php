<?php

namespace Hgabka\NodeBundle\Form;

use Hgabka\NodeBundle\Entity\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NodeMenuTabAdminType extends AbstractType
{
    /** @var AuthorizationCheckerInterface */
    protected $authChecker;

    /**
     * NodeMenuTabAdminType constructor.
     */
    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['available_in_nav']) {
            $builder->add('hiddenFromNav', CheckboxType::class, [
                'label' => 'hg_node.form.menu_tab.hidden_from_menu.label',
                'required' => false,
            ]);
        }
        if ($this->authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('internalName', TextType::class, [
                'label' => 'hg_node.form.menu_tab.internal_name.label',
                'required' => false,
            ]);
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'menu';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Node::class,
            'available_in_nav' => true,
        ]);
    }
}
