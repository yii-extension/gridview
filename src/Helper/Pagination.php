<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Helper;

use RuntimeException;

/**
 * Pagination represents information relevant to pagination of data items.
 *
 * When data needs to be rendered in multiple pages, Pagination can be used to represent information such as
 * {@see totalCount|total item count}, {@see pageSize|page size}, {@see currentPage| current page}, etc.
 *
 * These information can be passed to {@see LinkPager|pagers} to render pagination buttons or links.
 */
final class Pagination
{
    public const DEFAULT_PAGE_SIZE = 10;
    private int $currentPage = 1;
    private int $pageSize = self::DEFAULT_PAGE_SIZE;
    private int $totalCount = 0;
    private string $pageParam = 'page';
    private string $pageSizeParam = 'per-page';

    /**
     * Sets the current page number.
     *
     * @param int $currentPage the zero-based index of the current page.
     */
    public function currentPage(int $currentPage): void
    {
        if ($currentPage < 1) {
            throw new RuntimeException('Current page should be at least 1');
        }

        $this->currentPage = $currentPage;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return int the limit of the data. This may be used to set the LIMIT value for a SQL statement for fetching the
     * current page of data.
     */
    public function getLimit(): int
    {
        return $this->getPageSize();
    }

    /**
     * @return int the offset of the data. This may be used to set the OFFSET value for a SQL statement for fetching the
     * current page of data.
     */
    public function getOffset(): int
    {
        return $this->pageSize * ($this->currentPage - 1);
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->totalCount / $this->pageSize);
    }

    /**
     * @param string $pageParam name of the parameter storing the current page index.
     */
    public function pageParam(string $pageParam): void
    {
        $this->pageParam = $pageParam;
    }

    /**
     * @param string $pageSizeParam name of the parameter storing the page size.
     */
    public function pageSizeParam(string $pageSizeParam): void
    {
        $this->pageSizeParam = $pageSizeParam;
    }

    /**
     * @param int $pageSize number of items on each page.
     */
    public function pageSize(int $pageSize): void
    {
        if ($pageSize < 1) {
            throw new RuntimeException('Page size should be at least 1');
        }

        $this->pageSize = $pageSize;
    }

    public function totalCount(int $totalCount): self
    {
        $this->totalCount = $totalCount;

        return $this;
    }
}
