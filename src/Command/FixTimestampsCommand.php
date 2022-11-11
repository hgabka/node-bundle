<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'hgabka:nodes:fix-timestamps', description: 'Updates timestamps for all node translations', hidden: false)]
class FixTimestampsCommand extends Command
{
    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setHelp('The <info>hgabka:nodes:fix-timestamps</info> will loop over all node translation entries and update the timestamps for the entries.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;

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
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return Command::SUCCESS;
    }
}
