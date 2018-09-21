<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QueuedNodeTranslationAction.
 *
 * @ORM\Entity
 * @ORM\Table(name="hg_node_queued_node_translation_actions")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class QueuedNodeTranslationAction
{
    const ACTION_PUBLISH = 'publish';
    const ACTION_UNPUBLISH = 'unpublish';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var NodeTranslation
     *
     * @ORM\ManyToOne(targetEntity="NodeTranslation")
     * @ORM\JoinColumn(name="node_translation_id", referencedColumnName="id")
     */
    protected $nodeTranslation;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $action;

    /**
     * @var UserInterface
     *
     * The doctrine metadata is set dynamically in Hgabka\NodeBundle\EventListener\MappingListener
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $date;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return QueuedNodeTranslationAction
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set nodeTranslation.
     *
     * @param NodeTranslation $nodeTranslation
     *
     * @return QueuedNodeTranslationAction
     */
    public function setNodeTranslation(NodeTranslation $nodeTranslation)
    {
        $this->nodeTranslation = $nodeTranslation;

        return $this;
    }

    /**
     * Get NodeTranslation.
     *
     * @return NodeTranslation
     */
    public function getNodeTranslation()
    {
        return $this->nodeTranslation;
    }

    /**
     * Get action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set action.
     *
     * @param string $action
     *
     * @return QueuedNodeTranslationAction
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Set user.
     *
     * @param UserInterface $user
     *
     * @return QueuedNodeTranslationAction
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return QueuedNodeTranslationAction
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
