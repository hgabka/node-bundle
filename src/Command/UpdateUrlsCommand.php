<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * UpdateUrlsCommand.
 */
class UpdateUrlsCommand extends Command
{
    protected static $defaultName = 'hgabka:nodes:updateurls';

    /** @var EntityManagerInterface */
    private $entityManager;

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
            ->setDescription('Update all urls for all translations.')
            ->setHelp('The <info>hgabka:nodes:updateurls</info> will loop over all node translation entries and update the urls for the entries.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;

        $mainNodes = $em->getRepository(NodeTranslation::class)->getTopNodeTranslations();
        if (\count($mainNodes)) {
            // @var NodeTranslation $mainNode
            foreach ($mainNodes as $mainNode) {
                $mainNode->setUrl('');
                $em->persist($mainNode);
                $em->flush($mainNode);
            }
        }

        $output->writeln('Updated all nodes');

        return Command::SUCCESS;
    }
}
