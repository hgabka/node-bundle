<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ConvertSequenceNumberToWeightCommand.
 */
class ConvertSequenceNumberToWeightCommand extends Command
{
    protected static $defaultName = 'hgabka:nodes:convertsequencenumbertoweight';

    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::$defaultName)
            ->setDescription('Set all the nodetranslations weights based on the nodes sequencenumber')
            ->setHelp('The <info>Node:nodetranslations:updateweights</info> will loop over all nodetranslation and set their weight based on the nodes sequencenumber.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->entityManager;

        $batchSize = 20;
        $i = 0;
        $class = NodeTranslation::class;
        $q = $em->createQuery("SELECT t FROM $class t WHERE t.weight IS NULL");

        $iterableResult = $q->iterate();

        while (false !== ($row = $iterableResult->next())) {
            // @var NodeTranslation $nodeTranslation
            $nodeTranslation = $row[0];
            if (null === $nodeTranslation->getWeight()) {
                $output->writeln('- editing node: ' . $nodeTranslation->getTitle());
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

        return Command::SUCCESS;
    }
}
