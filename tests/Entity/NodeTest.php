<?php

namespace Hgabka\NodeBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Form\NodeAdminType;
use Hgabka\NodeBundle\Tests\Fixtures\ContentPage;
use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{
    private function node(int $id): Node
    {
        return (new Node())->setId($id);
    }

    private function translation(string $lang, bool $online): NodeTranslation
    {
        return (new NodeTranslation())->setLang($lang)->setOnline($online);
    }

    public function testNewNodeDefaults(): void
    {
        $node = $this->node(1);

        $this->assertFalse($node->isDeleted());
        $this->assertFalse($node->isHiddenFromNav());
        $this->assertFalse($node->getHiddenFromNav());
        $this->assertCount(0, $node->getChildren());
        $this->assertCount(0, $node->getNodeTranslations(true));
        $this->assertNull($node->getParent());
        $this->assertNull($node->getInternalName());
        $this->assertNull($node->getRefEntityName());
        $this->assertSame(NodeAdminType::class, $node->getDefaultAdminType());
    }

    public function testAFreshNodeCanBeInspectedBeforeItIsPersisted(): void
    {
        $node = new Node();

        $this->assertNull($node->getId());
        $this->assertSame('node , refEntityName: ', (string) $node);
    }

    public function testToString(): void
    {
        $node = $this->node(5)->setRef(new ContentPage());

        $this->assertSame('node 5, refEntityName: ' . ContentPage::class, (string) $node);
    }

    public function testAddNodeLinksParentAndChild(): void
    {
        $parent = $this->node(1);
        $child = $this->node(2);

        $this->assertSame($parent, $parent->addNode($child));

        $this->assertSame($parent, $child->getParent());
        $this->assertSame([$child], array_values($parent->getChildren()->toArray()));
    }

    public function testDeletedChildrenAreHidden(): void
    {
        $parent = $this->node(1);
        $parent->addNode($this->node(2));
        $parent->addNode($this->node(3)->setDeleted(true));

        $this->assertCount(1, $parent->getChildren());
    }

    public function testParentsAreReturnedFromRootDownwards(): void
    {
        $root = $this->node(1);
        $middle = $this->node(2);
        $leaf = $this->node(3);
        $root->addNode($middle);
        $middle->addNode($leaf);

        $this->assertSame([$root, $middle], $leaf->getParents());
        $this->assertSame([], $root->getParents());
    }

    public function testNodeTranslationsHideOfflineOnesByDefault(): void
    {
        $node = $this->node(1);
        $node->addNodeTranslation($this->translation('hu', true));
        $node->addNodeTranslation($this->translation('en', false));

        $this->assertCount(1, $node->getNodeTranslations());
        $this->assertCount(2, $node->getNodeTranslations(true));
    }

    public function testNodeTranslationLookupByLanguage(): void
    {
        $node = $this->node(1);
        $hu = $this->translation('hu', true);
        $en = $this->translation('en', false);
        $node->addNodeTranslation($hu)->addNodeTranslation($en);

        $this->assertSame($hu, $node->getNodeTranslation('hu'));
        $this->assertNull($node->getNodeTranslation('en'));
        $this->assertSame($en, $node->getNodeTranslation('en', true));
        $this->assertNull($node->getNodeTranslation('de', true));
        $this->assertSame($node, $hu->getNode());
    }

    public function testSortedChildrenCanBeOrderedByAPublicField(): void
    {
        $parent = $this->node(1);
        $first = $this->node(2);
        $second = $this->node(3);
        $parent->addNode($first);
        $parent->addNode($second);

        $this->assertSame([$second, $first], array_values($parent->getChildrenSorted(['id' => 'DESC'])->toArray()));
    }

    public function testChildrenAreSortedByTheLeftValueByDefault(): void
    {
        $parent = $this->node(1);
        $first = $this->node(2);
        $second = $this->node(3);
        foreach ([[$second, 20], [$first, 10]] as [$child, $left]) {
            (new \ReflectionProperty(Node::class, 'lft'))->setValue($child, $left);
            $parent->addNode($child);
        }

        $this->assertSame([$first, $second], array_values($parent->getChildrenSorted()->toArray()));
        $this->assertSame(10, $first->getLeft());
        $this->assertSame(10, $first->getLft());
    }

    public function testSettersAreFluent(): void
    {
        $node = $this->node(1);

        $this->assertSame($node, $node->setInternalName('home')->setHiddenFromNav(true)->setChildren(new ArrayCollection()));
        $this->assertSame('home', $node->getInternalName());
        $this->assertTrue($node->isHiddenFromNav());
        $node->setSibling($other = $this->node(2));
        $this->assertSame($other, $node->getSibling());
    }
}
