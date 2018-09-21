<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixTimestampsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('hgabka:nodes:fix-timestamps')
            ->setDescription('Update timestamps for all node translations.')
            ->setHelp('The <info>hgabka:nodes:fix-timestamps</info> will loop over all node translation entries and update the timestamps for the entries.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $db = $em->getConnection();
        $db->beginTransaction();

        try {
            $sql = <<<'SQL'
update hg_node_node_translations nt
set nt.created=(select MIN(created) from hg_node_node_versions nv where nv.node_translation_id=nt.id AND nv.type='public'),
nt.updated=(select MAX(updated) from hg_node_node_versions nv where nv.node_translation_id=nt.id AND nv.type='public')
SQL;

            $db->exec($sql);
            $db->commit();
            $output->writeln('Updated all node translation timestamps');
        } catch (DBALException $e) {
            $db->rollBack();
            $output->writeln('<error>An error occured while updating the node translation timestamps</error>');
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }
    }
}
