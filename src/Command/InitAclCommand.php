<?php

namespace Hgabka\NodeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentityRetrievalStrategy;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;

/**
 * Basic initialization of ACL entries for all nodes.
 */
class InitAclCommand extends Command
{
    protected static $defaultName = 'hgabka:init:acl';

    /** @var ObjectIdentityRetrievalStrategy */
    protected $oiaStrategy;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var AclProviderInterface */
    private $aclProvider;

    /** @var string */
    private $publicAccessRole;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();

        $this->entityManager = $manager;
    }

    public function setAclProvider(AclProviderInterface $provider)
    {
        $this->aclProvider = $provider;
    }

    /**
     * @param ObjectIdentityRetrievalStrategy $oiaStrategy
     *
     * @return InitAclCommand
     */
    public function setOiaStrategy($oiaStrategy)
    {
        $this->oiaStrategy = $oiaStrategy;

        return $this;
    }

    /**
     * @param string $publicAccessRole
     * @return InitAclCommand
     */
    public function setPublicAccessRole(string $publicAccessRole): InitAclCommand
    {
        $this->publicAccessRole = $publicAccessRole;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::$defaultName)
            ->setDescription('Basic initialization of ACL for projects')
            ->setHelp('The <info>hgabka:init:acl</info> will create basic ACL entries for the nodes of the current project');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @var EntityManager $em
        $em = $this->entityManager;
        // @var MutableAclProviderInterface $aclProvider
        $aclProvider = $this->aclProvider;
        // @var ObjectIdentityRetrievalStrategyInterface $oidStrategy
        $oidStrategy = $this->oiaStrategy;

        // Fetch all nodes & grant access
        $nodes = $em->getRepository(Node::class)->findAll();
        $count = 0;
        foreach ($nodes as $node) {
            ++$count;
            $objectIdentity = $oidStrategy->getObjectIdentity($node);

            try {
                $aclProvider->deleteAcl($objectIdentity);
            } catch (AclNotFoundException $e) {
                // Do nothing
            }
            $acl = $aclProvider->createAcl($objectIdentity);

            $securityIdentity = new RoleSecurityIdentity($this->publicAccessRole);
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_VIEW);

            $securityIdentity = new RoleSecurityIdentity('ROLE_ADMIN');
            $acl->insertObjectAce(
                $securityIdentity,
                MaskBuilder::MASK_VIEW | MaskBuilder::MASK_EDIT | MaskBuilder::MASK_PUBLISH | MaskBuilder::MASK_UNPUBLISH
            );

            $securityIdentity = new RoleSecurityIdentity('ROLE_SUPER_ADMIN');
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_IDDQD);
            $aclProvider->updateAcl($acl);
        }
        $output->writeln("{$count} nodes processed.");

        return Command::SUCCESS;
    }
}
