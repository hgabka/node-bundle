<?php

namespace Hgabka\NodeBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Hgabka\NodeBundle\Entity\QueuedNodeTranslationAction;

/**
 * Class MappingListener.
 */
class MappingListener
{
    public function __construct(private readonly ?string $className)
    {
    }

    /**
     * Called when class meta data is fetched.
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
