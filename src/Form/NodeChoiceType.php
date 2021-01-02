<?php

namespace Hgabka\NodeBundle\Form;

use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Repository\NodeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NodeChoiceType extends AbstractType
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'page_class' => [],
                'locale' => null,
                'online' => true,
                'class' => Node::class,
                'choice_label' => 'nodeTranslations[0].title',
                'query_builder' => function (NodeRepository $er) {
                    return $er->createQueryBuilder('n');
                },
            ]
        );

        $queryBuilderNormalizer = function (Options $options, $queryBuilder) {
            if (\is_callable($queryBuilder)) {
                $queryBuilder = \call_user_func($queryBuilder, $options['em']->getRepository($options['class']));
            }

            if (!empty($options['page_class'])) {
                $queryBuilder
                    ->select('n, nt')
                    ->innerJoin('n.nodeTranslations', 'nt')
                    ->innerJoin('nt.publicNodeVersion', 'nv')
                    ->andWhere('nt.online = :online')
                    ->andWhere('nt.lang = :lang')
                    ->andWhere('n.deleted != 1')
                    ->andWhere('n.refEntityName IN(:refEntityName)')
                    ->setParameter('lang', $options['locale'] ?: $this->getCurrentLocale())
                    ->setParameter('refEntityName', $options['page_class'])
                    ->setParameter('online', $options['online']);
            }

            return $queryBuilder;
        };

        $resolver->setNormalizer('query_builder', $queryBuilderNormalizer);
        $resolver->setAllowedTypes('query_builder', ['null', 'callable', 'Doctrine\ORM\QueryBuilder']);
    }

    public function getParent()
    {
        return EntityType::class;
    }

    private function getCurrentLocale()
    {
        if (null === $this->requestStack->getCurrentRequest()) {
            return null;
        }

        return $this->requestStack->getCurrentRequest()->getLocale();
    }
}
