<?php

namespace Hgabka\NodeBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Hgabka\NodeBundle\Entity\QueuedNodeTranslationAction;

/**
 * Class MappingListener.
 */
class MappingListener
{
    /**
     * @var string
     */
    private $className;

    /**
     * Constructor.
     *
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Called when class meta data is fetched.
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $entityName = (string) ($classMetadata->getName());

        // We dynamically set the user class that was configured in the configuration
        if (QueuedNodeTranslationAction::class === $entityName) {
            $mapping = [
                'fieldName' => 'user',
                'targetEntity' => $this->className,
                'joinColumns' => [[
                    'name' => 'user_id',
                    'referencedColumnName' => 'id',
                    'unique' => false,
                    'nullable' => true,
                ]],
            ];
            $classMetadata->mapManyToOne($mapping);
        }
    }
}
