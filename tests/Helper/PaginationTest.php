<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Helper;

use RuntimeException;
use Yii\Extension\GridView\Tests\TestCase;

final class PaginationTest extends TestCase
{
    public function testCurrentPage(): void
    {
        $this->pagination->currentPage(1);
        $this->assertSame(1, $this->pagination->getCurrentPage());
    }

    public function testCurrentPageException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Current page should be at least 1');
        $this->pagination->currentPage(0);
    }

    public function testPageParam(): void
    {
        $this->pagination->pageParam('pager');
        $this->assertSame('pager', $this->getInaccessibleProperty($this->pagination, 'pageParam'));
    }

    public function testPageSizeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Page size should be at least 1');
        $this->pagination->pageSize(0);
    }

    public function testPageSizeParam(): void
    {
        $this->pagination->pageSizeParam('per-page');
        $this->assertSame('per-page', $this->getInaccessibleProperty($this->pagination, 'pageSizeParam'));
    }

    public function testTotalCount(): void
    {
        $this->pagination->totalCount(1);
        $this->assertSame(1, $this->pagination->getTotalCount());
    }
}
