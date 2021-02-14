<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Helper;

use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Router\UrlMatcherInterface;

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
    private const LINK_NEXT = 'next';
    private const LINK_PREV = 'prev';
    private const LINK_FIRST = 'first';
    private const LINK_LAST = 'last';
    private int $currentPage = 1;
    private int $pageSize = 10;
    private int $totalCount = 0;
    private string $pageParam = 'page';
    private string $pageSizeParam = 'per-page';
    private UrlGeneratorInterface $urlGenerator;
    private UrlMatcherInterface $urlMatcher;

    public function __construct(UrlGeneratorInterface $urlGenerator, UrlMatcherInterface $urlMatcher)
    {
        $this->urlGenerator = $urlGenerator;
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * Sets the current page number.
     *
     * @param int $value the zero-based index of the current page.
     */
    public function currentPage(int $currentPage): self
    {
        if ($currentPage < 1) {
            throw new \RuntimeException('Page size should be at least 1');
        }

        $this->currentPage = $currentPage;

        return $this;
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
        $pageSize = $this->getPageSize();

        return $pageSize;
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
     *
     * @return $this
     */
    public function pageParam(string $pageParam): self
    {
        $new = clone $this;
        $new->pageParam = $pageParam;

        return $this;
    }

    /**
     * @param string $pageSizeParam name of the parameter storing the page size.
     *
     * @return $this
     */
    public function pageSizeParam(string $pageSizeParam): self
    {
        $new = clone $this;
        $new->pageSizeParam = $pageSizeParam;

        return $this;
    }

    /**
     * @param int $pageSize number of items on each page.
     *
     * @return $this
     */
    public function pageSize(int $pageSize): self
    {
        if ($pageSize < 1) {
            throw new \RuntimeException('Page size should be at least 1');
        }

        $this->pageSize = $pageSize;

        return $this;
    }

    public function totalCount(int $totalCount): self
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    /**
     * Creates the URL suitable for pagination with the specified page number. This method is mainly called by pagers
     * when creating URLs used to perform pagination.
     *
     * @param int $page the zero-based page number that the URL should point to.
     * @param int $pageSize the number of items on each page. If not set, the value of {@see pageSize} will be used.
     * @param bool $absolute whether to create an absolute URL. Defaults to `false`.
     *
     * @return string the created URL.
     *
     * {@see params}
     * {@see forcePageParam}
     */
    private function createUrl(int $page, int $pageSize = null, bool $absolute = false): string
    {
        $action = $this->urlMatcher->getCurrentRoute()->getName();

        return $this->urlGenerator->generate($action, [$this->pageParam => $page]);
    }
}
