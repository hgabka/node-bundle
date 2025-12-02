<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'hgabka:nodes:convertsequencenumbertoweight', description: 'Sets all the nodetranslations weights based on the nodes sequencenumber', hidden: false)]
class ConvertSequenceNumberToWeightCommand extends Command
{
    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('The <info>Node:nodetranslations:updateweights</info> will loop over all nodetranslation and set their weight based on the nodes sequencenumber.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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
