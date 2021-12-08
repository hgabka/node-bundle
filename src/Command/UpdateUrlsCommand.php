<?php

namespace Hgabka\NodeBundle\Command;

use Hgabka\NodeBundle\Entity\NodeTranslation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * UpdateUrlsCommand.
 */
class UpdateUrlsCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('hgabka:nodes:updateurls')
            ->setDescription('Update all urls for all translations.')
            ->setHelp('The <info>hgabka:nodes:updateurls</info> will loop over all node translation entries and update the urls for the entries.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

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
    }
}
