<?php

namespace Hgabka\NodeBundle\Tests\Helper;

use Hgabka\NodeBundle\Helper\PagesConfiguration;
use Hgabka\NodeBundle\Tests\Fixtures\ContentPage;
use Hgabka\NodeBundle\Tests\Fixtures\HomePage;
use PHPUnit\Framework\TestCase;

class PagesConfigurationTest extends TestCase
{
    public function testDefaultsAreDerivedFromThePageClass(): void
    {
        $config = new PagesConfiguration([]);

        $this->assertSame('ContentPage', $config->getName(ContentPage::class));
        $this->assertNull($config->getIcon(ContentPage::class));
        $this->assertFalse($config->isHiddenFromTree(ContentPage::class));
        $this->assertTrue($config->isHiddenFromTree(HomePage::class));
        $this->assertTrue($config->isIndexable(ContentPage::class));
        $this->assertSame(ContentPage::class, $config->getSearchType(ContentPage::class));
        $this->assertFalse($config->isStructureNode(ContentPage::class));
        $this->assertTrue($config->isStructureNode(HomePage::class));
        $this->assertFalse($config->isHomePage(ContentPage::class));
        $this->assertTrue($config->isHomePage(HomePage::class));
    }

    public function testConfiguredValuesOverrideTheDefaults(): void
    {
        $config = new PagesConfiguration([
            ContentPage::class => ['name' => 'Content', 'icon' => 'fa-file', 'hidden_from_tree' => true, 'indexable' => false, 'search_type' => 'content', 'structure_node' => true, 'is_homepage' => true],
        ]);

        $this->assertSame('Content', $config->getName(ContentPage::class));
        $this->assertSame('fa-file', $config->getIcon(ContentPage::class));
        $this->assertTrue($config->isHiddenFromTree(ContentPage::class));
        $this->assertFalse($config->isIndexable(ContentPage::class));
        $this->assertSame('content', $config->getSearchType(ContentPage::class));
        $this->assertTrue($config->isStructureNode(ContentPage::class));
        $this->assertTrue($config->isHomePage(ContentPage::class));
    }

    public function testObjectsAreResolvedByTheirClass(): void
    {
        $config = new PagesConfiguration([ContentPage::class => ['name' => 'Content']]);

        $this->assertSame('Content', $config->getName(ContentPage::class));
        $this->assertFalse($config->isHomePage(new ContentPage()));
        $this->assertTrue($config->isHomePage(new HomePage()));
    }

    public function testPossibleChildTypesGetTheirNameFilledIn(): void
    {
        $config = new PagesConfiguration([HomePage::class => ['name' => 'Home']]);

        $types = $config->getPossibleChildTypes(ContentPage::class);

        $this->assertSame(
            [
                ['name' => 'Sub page', 'class' => ContentPage::class],
                ['class' => HomePage::class, 'name' => 'Home'],
            ],
            $types
        );
    }

    public function testConfiguredAllowedChildrenReplaceThePageDefinition(): void
    {
        $config = new PagesConfiguration([ContentPage::class => ['allowed_children' => [['class' => HomePage::class]]]]);

        $this->assertSame([['class' => HomePage::class, 'name' => 'HomePage']], $config->getPossibleChildTypes(ContentPage::class));
    }

    public function testHomepageTypesListsConfiguredHomePages(): void
    {
        $config = new PagesConfiguration([
            ContentPage::class => ['name' => 'Content'],
            HomePage::class => ['name' => 'Home'],
        ]);

        $this->assertSame([HomePage::class => 'Home'], $config->getHomepageTypes());
    }
}
