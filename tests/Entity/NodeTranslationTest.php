<?php

namespace Hgabka\NodeBundle\Tests\Entity;

use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Form\NodeTranslationAdminType;
use PHPUnit\Framework\TestCase;

class NodeTranslationTest extends TestCase
{
    private function translation(?Node $node, string $lang, ?string $slug): NodeTranslation
    {
        $translation = (new NodeTranslation())->setLang($lang)->setSlug($slug);
        $node?->addNodeTranslation($translation);

        return $translation;
    }

    public function testDefaults(): void
    {
        $translation = new NodeTranslation();

        $this->assertFalse($translation->isOnline());
        $this->assertInstanceOf(\DateTime::class, $translation->getCreated());
        $this->assertInstanceOf(\DateTime::class, $translation->getUpdated());
        $this->assertCount(0, $translation->getNodeVersions());
        $this->assertNull($translation->getPublicNodeVersion());
        $this->assertSame(NodeTranslationAdminType::class, $translation->getDefaultAdminType());
    }

    public function testSlugPartOfARootNodeIsItsOwnSlug(): void
    {
        $node = (new Node())->setId(1);
        $translation = $this->translation($node, 'hu', 'rolunk');

        $this->assertSame('rolunk', $translation->getSlugPart());
        $this->assertSame('rolunk', $translation->getFullSlug());
    }

    public function testSlugPartIsBuiltFromTheAncestors(): void
    {
        $root = (new Node())->setId(1);
        $middle = (new Node())->setId(2);
        $leaf = (new Node())->setId(3);
        $root->addNode($middle);
        $middle->addNode($leaf);
        $this->translation($root, 'hu', 'cegunk');
        $this->translation($middle, 'hu', 'csapat');
        $this->translation($root, 'en', 'company');
        $leafTranslation = $this->translation($leaf, 'hu', 'vezetoseg');

        $this->assertSame('cegunk/csapat/vezetoseg', $leafTranslation->getFullSlug());
    }

    public function testParentWithoutSlugIsSkippedAndTrailingSlashIsNormalised(): void
    {
        $root = (new Node())->setId(1);
        $leaf = (new Node())->setId(2);
        $root->addNode($leaf);
        $this->translation($root, 'hu', '');
        $this->assertSame('x', $this->translation($leaf, 'hu', 'x')->getSlugPart());

        $root2 = (new Node())->setId(3);
        $leaf2 = (new Node())->setId(4);
        $root2->addNode($leaf2);
        $this->translation($root2, 'hu', 'a/');
        $this->assertSame('a/b', $this->translation($leaf2, 'hu', 'b')->getSlugPart());
    }

    public function testEmptySlugHasNoFullSlug(): void
    {
        $translation = $this->translation((new Node())->setId(1), 'hu', '');

        $this->assertNull($translation->getFullSlug());
    }

    public function testParentWithoutTranslationInTheLanguageIsIgnored(): void
    {
        $root = (new Node())->setId(1);
        $leaf = (new Node())->setId(2);
        $root->addNode($leaf);
        $this->translation($root, 'en', 'company');

        $this->assertSame('vezetoseg', $this->translation($leaf, 'hu', 'vezetoseg')->getFullSlug());
    }

    public function testPublicVersionIsTrackedWhenAdded(): void
    {
        $translation = new NodeTranslation();
        $public = (new NodeVersion())->setType(NodeVersion::PUBLIC_VERSION);
        $draft = (new NodeVersion())->setType(NodeVersion::DRAFT_VERSION);

        $translation->addNodeVersion($public)->addNodeVersion($draft);

        $this->assertSame($public, $translation->getPublicNodeVersion());
        $this->assertSame($public, $translation->getNodeVersion('public'));
        $this->assertSame($draft, $translation->getDraftNodeVersion());
        $this->assertSame($translation, $draft->getNodeTranslation());
        $this->assertNull($translation->getNodeVersion('archive'));
    }

    public function testNewestVersionOfATypeWins(): void
    {
        $translation = new NodeTranslation();
        $old = (new NodeVersion())->setType('draft');
        $new = (new NodeVersion())->setType('draft');

        $translation->addNodeVersion($old)->addNodeVersion($new);

        $this->assertSame($new, $translation->getDraftNodeVersion());
    }

    public function testAccessors(): void
    {
        $translation = (new NodeTranslation())->setUrl('/a')->setWeight(3)->setTitle('Title')->setOnline(true)->setId(9);

        $this->assertSame(['/a', 3, 'Title', true, 9], [$translation->getUrl(), $translation->getWeight(), $translation->getTitle(), $translation->isOnline(), $translation->getId()]);
    }
}
