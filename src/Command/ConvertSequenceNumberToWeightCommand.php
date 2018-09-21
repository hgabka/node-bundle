<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\ORM\EntityManager;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ConvertSequenceNumberToWeightCommand.
 */
class ConvertSequenceNumberToWeightCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('hgabka:nodes:convertsequencenumbertoweight')
            ->setDescription('Set all the nodetranslations weights based on the nodes sequencenumber')
            ->setHelp('The <info>Node:nodetranslations:updateweights</info> will loop over all nodetranslation and set their weight based on the nodes sequencenumber.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @var EntityManager $em
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $batchSize = 20;
        $i = 0;
        $class = NodeTranslation::class;
        $q = $em->createQuery("SELECT t FROM $class t WHERE t.weight IS NULL");

        $iterableResult = $q->iterate();

        while (false !== ($row = $iterableResult->next())) {
            // @var NodeTranslation $nodeTranslation
            $nodeTranslation = $row[0];
            if (null === $nodeTranslation->getWeight()) {
                $output->writeln('- editing node: '.$nodeTranslation->getTitle());
                $nodeTranslation->setWeight($nodeTranslation->getNode()->getSequenceNumber());
                $em->persist($nodeTranslation);

                ++$i;
            }
            if (0 === ($i % $batchSize)) {
                $output->writeln('FLUSHING!');
                $em->flush();
                $em->clear();
            }
        }

        $output->writeln('FLUSHING!');
        $em->flush();
        $em->clear();

        $output->writeln('Updated all nodes');
    }
}
