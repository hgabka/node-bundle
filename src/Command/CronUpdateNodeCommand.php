<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\QueuedNodeTranslationAction;
use Hgabka\NodeBundle\Helper\NodeAdmin\NodeAdminPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class CronUpdateNodeCommand extends Command
{
    protected static $defaultName = 'hgabka:nodes:cron';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly NodeAdminPublisher $publisher,
        private readonly string $adminFirewallName
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::$defaultName)
            ->setDescription('Do everything that needs to be run in a cron job.')
            ->setHelp('The <info>hgabka:nodes:cron</info> will loop over all queued node translation action entries and update the nodetranslations if needed.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;

        $queuedNodeTranslationActions = $em->getRepository(QueuedNodeTranslationAction::class)->findAll();

        if (\count($queuedNodeTranslationActions)) {
            foreach ($queuedNodeTranslationActions as $queuedNodeTranslationAction) {
                $now = new \DateTime();
                if ($queuedNodeTranslationAction->getDate()->getTimestamp() < $now->getTimestamp()) {
                    $action = $queuedNodeTranslationAction->getAction();

                    // Set user security context
                    $user = $queuedNodeTranslationAction->getUser();
                    $runAsToken = new UsernamePasswordToken($user, $this->adminFirewallName, $user->getRoles());
                    $this->tokenStorage->setToken($runAsToken);

                    $nodeTranslation = $queuedNodeTranslationAction->getNodeTranslation();
                    switch ($action) {
                        case QueuedNodeTranslationAction::ACTION_PUBLISH:
                            $this->publisher->publish($nodeTranslation, $user);
                            $output->writeln('Published the page ' . $nodeTranslation->getTitle());

                            break;
                        case QueuedNodeTranslationAction::ACTION_UNPUBLISH:
                            $this->publisher->unPublish($nodeTranslation);
                            $output->writeln('Unpublished the page ' . $nodeTranslation->getTitle());

                            break;
                        default:
                            $output->writeln("Don't understand the action " . $action);
                    }
                }
            }
            $output->writeln('Done');
        } else {
            $output->writeln('No queued jobs');
        }

        return Command::SUCCESS;
    }
}
