<?php

namespace Hgabka\NodeBundle\Tests\Entity;

use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Tests\Fixtures\ContentPage;
use PHPUnit\Framework\TestCase;

class NodeVersionTest extends TestCase
{
    public function testTypePredicates(): void
    {
        $version = new NodeVersion();
        $this->assertFalse($version->isDraft());
        $this->assertFalse($version->isPublic());

        $this->assertTrue($version->setType('draft')->isDraft());
        $this->assertTrue($version->setType('public')->isPublic());
        $this->assertFalse($version->isDraft());
    }

    public function testRefPointsAtThePage(): void
    {
        $page = new ContentPage();
        $page->setId(11);

        $version = (new NodeVersion())->setRef($page);

        $this->assertSame(11, $version->getRefId());
        $this->assertSame(ContentPage::class, $version->getRefEntityName());
    }

    public function testTimestampsAndOwner(): void
    {
        $version = (new NodeVersion())->setOwner('admin');
        $date = new \DateTime('2024-01-01');
        $version->setCreated($date)->setUpdated($date);

        $this->assertSame('admin', $version->getOwner());
        $this->assertSame($date, $version->getCreated());
        $this->assertSame($date, $version->getUpdated());
        $this->assertNull($version->getDefaultAdminType());
    }

    public function testOrigin(): void
    {
        $origin = new NodeVersion();

        $version = (new NodeVersion())->setOrigin($origin);

        $this->assertSame($origin, $version->getOrigin());
    }
}
