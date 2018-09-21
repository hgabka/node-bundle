<?php

namespace Kunstmaan\NodeBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Kunstmaan\NodeBundle\Entity\Node;
use Kunstmaan\NodeBundle\Entity\NodeTranslation;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-09-14 at 11:09:16.
 *
 * @coversNothing
 */
class NodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Node
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Node();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::isHiddenFromNav
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getHiddenFromNav
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setHiddenFromNav
     */
    public function testIsGetSetHiddenFromNav()
    {
        $this->assertFalse($this->object->getHiddenFromNav());
        $this->object->setHiddenFromNav(true);
        $this->assertTrue($this->object->isHiddenFromNav());
        $this->assertTrue($this->object->getHiddenFromNav());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::__construct
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getChildren
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setChildren
     */
    public function testGetSetChildren()
    {
        $children = new ArrayCollection();
        $child = new Node();
        $children->add($child);
        $this->object->setChildren($children);

        $this->assertSame(1, $this->object->getChildren()->count());
        $this->assertSame($children, $this->object->getChildren());
        $this->assertTrue($this->object->getChildren()->contains($child));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getChildren
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setChildren
     */
    public function testGetSetChildrenWithDeletedChildren()
    {
        $children = new ArrayCollection();
        $child = new Node();
        $deletedChild = new Node();
        $deletedChild->setDeleted(true);
        $children->add($child);
        $children->add($deletedChild);
        $this->object->setChildren($children);

        $this->assertSame(1, $this->object->getChildren()->count());
        $this->assertTrue($this->object->getChildren()->contains($child));
        $this->assertFalse($this->object->getChildren()->contains($deletedChild));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::addNode
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setParent
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getParent
     */
    public function testAddNode()
    {
        $child = new Node();
        $this->object->addNode($child);
        $this->assertSame($this->object, $child->getParent());
        $this->assertSame(1, $this->object->getChildren()->count());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getNodeTranslations
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setNodeTranslations
     */
    public function testGetSetNodeTranslations()
    {
        $translations = new ArrayCollection();
        $translation = new NodeTranslation();
        $translations->add($translation);
        $this->object->setNodeTranslations($translations);

        $this->assertSame(1, $this->object->getNodeTranslations(true)->count());
        $this->assertSame($translations, $this->object->getNodeTranslations(true));
        $this->assertTrue($this->object->getNodeTranslations(true)->contains($translation));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::addNodeTranslation
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getNodeTranslations
     */
    public function testGetNodeTranslationsWithOfflineNodes()
    {
        $translation1 = new NodeTranslation();
        $translation1->setOnline(true);
        $this->object->addNodeTranslation($translation1);

        $translation2 = new NodeTranslation();
        $translation2->setOnline(false);
        $this->object->addNodeTranslation($translation2);

        $this->assertSame(2, $this->object->getNodeTranslations(true)->count());
        $this->assertSame(1, $this->object->getNodeTranslations()->count());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::addNodeTranslation
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getNodeTranslation
     */
    public function testGetNodeTranslation()
    {
        $translation1 = new NodeTranslation();
        $translation1->setLang('nl');
        $translation1->setOnline(true);
        $this->object->addNodeTranslation($translation1);

        $translation2 = new NodeTranslation();
        $translation2->setLang('fr');
        $translation2->setOnline(true);
        $this->object->addNodeTranslation($translation2);

        $this->assertSame($translation1, $this->object->getNodeTranslation('nl'));
        $this->assertSame($translation2, $this->object->getNodeTranslation('fr'));
        $this->assertNotSame($translation1, $this->object->getNodeTranslation('fr'));
        $this->assertNotSame($translation2, $this->object->getNodeTranslation('nl'));
        $this->assertNull($this->object->getNodeTranslation('en'));
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getParents
     */
    public function testGetParents()
    {
        $child = new Node();
        $grandChild = new Node();
        $child->addNode($grandChild);
        $this->object->addNode($child);
        $parents = $grandChild->getParents();

        $this->assertSame(2, count($parents));
        $this->assertSame($child, $parents[1]);
        $this->assertSame($this->object, $parents[0]);
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::isDeleted
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setDeleted
     */
    public function testIsSetDeleted()
    {
        $this->assertFalse($this->object->isDeleted());
        $this->object->setDeleted(true);
        $this->assertTrue($this->object->isDeleted());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setRef
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setRefEntityName
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getRefEntityName
     */
    public function testSetRefAndGetRefEntityName()
    {
        $entity = new TestEntity();
        $this->object->setRef($entity);
        $this->assertSame('Kunstmaan\NodeBundle\Tests\Entity\TestEntity', $this->object->getRefEntityName());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::setInternalName
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getInternalName
     */
    public function testSetInternalName()
    {
        $this->object->setInternalName('AnInternalName');
        $this->assertSame('AnInternalName', $this->object->getInternalName());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::getDefaultAdminType
     */
    public function testGetDefaultAdminType()
    {
        $this->assertSame('Kunstmaan\NodeBundle\Form\NodeAdminType', $this->object->getDefaultAdminType());
    }

    /**
     * @covers \Kunstmaan\NodeBundle\Entity\Node::__toString
     */
    public function testToString()
    {
        $this->object->setId(1);
        $this->object->setRef(new TestEntity());

        $this->assertSame('node 1, refEntityName: Kunstmaan\NodeBundle\Tests\Entity\TestEntity', $this->object->__toString());
    }
}
