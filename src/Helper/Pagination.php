<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Helper;

use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Router\UrlMatcherInterface;

/**
 * Pagination represents information relevant to pagination of data items.
 *
 * When data needs to be rendered in multiple pages, Pagination can be used to represent information such as
 * {@see totalCount|total item count}, {@see pageSize|page size}, {@see page|current page}, etc. These information can
 * be passed to {@see LinkPager|pagers} to render pagination buttons or links.
 *
 * The following example shows how to create a pagination object and feed it to a pager.
 *
 * Controller action:
 *
 * ```php
 * ```
 *
 * View:
 *
 * ```php
 * ```
 *
 * For more details and usage information on Pagination, see the [guide article on pagination](guide:output-pagination).
 *
 * @property-read int $limit The limit of the data. This may be used to set the LIMIT value for a SQL statement for
 * fetching the current page of data. Note that if the page size is infinite, a value -1 will be returned. This property
 * is read-only.
 * @property-read array $links The links for navigational purpose. The array keys specify the purpose of the links
 * (e.g. [[LINK_FIRST]]), and the array values are the corresponding URLs. This property is read-only.
 * @property-read int $offset The offset of the data. This may be used to set the OFFSET value for a SQL
 * statement for fetching the current page of data. This property is read-only.
 * @property int $page The zero-based current page number.
 * @property-read int $pageCount Number of pages. This property is read-only.
 * @property int $pageSize The number of items per page. If it is less than 1, it means the page size is infinite, and
 * thus a single page contains all items.
 */
final class Pagination
{
    public const LINK_NEXT = 'next';
    public const LINK_PREV = 'prev';
    public const LINK_FIRST = 'first';
    public const LINK_LAST = 'last';

    /**
     * @var string name of the parameter storing the current page index.
     * @see params
     */
    public $pageParam = 'page';

    /**
     * @var string name of the parameter storing the page size.
     * @see params
     */
    public $pageSizeParam = 'per-page';

    /**
     * @var bool whether to always have the page parameter in the URL created by {@see createUrl()}.
     * If false and {@see page} is 0, the page parameter will not be put in the URL.
     */
    public $forcePageParam = true;

    /**
     * @var string the route of the controller action for displaying the paged contents.
     * If not set, it means using the currently requested route.
     */
    public $route;

    /**
     * @var array parameters (name => value) that should be used to obtain the current page number
     * and to create new pagination URLs. If not set, all parameters from $_GET will be used instead.
     *
     * In order to add hash to all links use `array_merge($_GET, ['#' => 'my-hash'])`.
     *
     * The array element indexed by {@see pageParam} is considered to be the current page number (defaults to 0);
     * while the element indexed by {@see pageSizeParam} is treated as the page size (defaults to
     * {@see defaultPageSize}).
     */
    public $params;

    /**
     * @var bool whether to check if {@see page} is within valid range.
     * When this property is true, the value of {@see page} will always be between 0 and ({@see pageCount}-1).
     * Because {@see pageCount} relies on the correct value of {@see totalCount} which may not be available in some
     * cases (e.g. MongoDB), you may want to set this property to be false to disable the page number validation.
     * By doing so, {@see page} will return the value indexed by {@see pageParam} in {@see params}.
     */
    public $validatePage = true;

    /**
     * @var int total number of items.
     */
    public $totalCount = 0;

    /**
     * @var int the default page size. This property will be returned by {@see pageSize} when page size cannot be
     * determined by {@see pageSizeParam} from {@see params}.
     */
    public $defaultPageSize = 20;

    /**
     * @var array|false the page size limits. The first array element stands for the minimal page size, and the
     * second the maximal page size. If this is false, it means [[pageSize]] should always return the value of
     * {@see defaultPageSize}.
     */
    public $pageSizeLimit = [1, 50];

    /**
     * @var int|null number of items on each page.
     * If it is less than 1, it means the page size is infinite, and thus a single page contains all items.
     */
    public ?int $pageSize = 5;

    public ?int $page = null;

    private UrlGeneratorInterface $urlGenerator;
    private UrlMatcherInterface $urlMatcher;

    public function __construct(UrlGeneratorInterface $urlGenerator, UrlMatcherInterface $urlMatcher)
    {
        $this->urlGenerator = $urlGenerator;
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * @return int number of pages
     */
    public function getPageCount(): int
    {
        $pageSize = $this->getPageSize();

        if ($pageSize < 1) {
            return $this->totalCount > 0 ? 1 : 0;
        }

        $totalCount = $this->totalCount < 0 ? 0 : (int) $this->totalCount;

        return (int) (($totalCount + $pageSize - 1) / $pageSize);
    }

    /**
     * Returns the zero-based current page number.
     *
     * @param bool $recalculate whether to recalculate the current page based on the page size and item count.
     *
     * @return int the zero-based current page number.
     */
    public function getPage($recalculate = false): int
    {
        if ($this->page === null || $recalculate) {
            $page = 1 - 1;
            $this->setPage($page, true);
        }

        return $this->page;
    }

    /**
     * Sets the current page number.
     *
     * @param int $value the zero-based index of the current page.
     * @param bool $validatePage whether to validate the page number. Note that in order
     *
     * to validate the page number, both {@see validatePage} and this parameter must be true.
     */
    public function setPage($value, $validatePage = false): void
    {
        if ($value === null) {
            $this->page = null;
        } else {
            $value = (int) $value;

            if ($validatePage && $this->validatePage) {
                $pageCount = $this->getPageCount();
                if ($value >= $pageCount) {
                    $value = $pageCount - 1;
                }
            }

            if ($value < 0) {
                $value = 0;
            }

            $this->page = $value;
        }
    }

    /**
     * Returns the number of items per page.
     *
     * By default, this method will try to determine the page size by {@see pageSizeParam} in {@see params}.
     * If the page size cannot be determined this way, {@see defaultPageSize} will be returned.
     *
     * @return int the number of items per page. If it is less than 1, it means the page size is infinite,
     * and thus a single page contains all items.
     *
     * {@see pageSizeLimit}
     */
    public function getPageSize(): int
    {
        if ($this->pageSize === null) {
            if (empty($this->pageSizeLimit) || !isset($this->pageSizeLimit[0], $this->pageSizeLimit[1])) {
                $pageSize = $this->defaultPageSize;
                $this->setPageSize($pageSize);
            } else {
                $pageSize = 1;
                $this->setPageSize($pageSize, true);
            }
        }

        return $this->pageSize;
    }

    /**
     * @param int $value the number of items per page.
     * @param bool $validatePageSize whether to validate page size.
     */
    public function setPageSize($value, $validatePageSize = false): void
    {
        if ($value === null) {
            $this->pageSize = null;
        } else {
            $value = (int) $value;
            if ($validatePageSize && isset($this->pageSizeLimit[0], $this->pageSizeLimit[1])) {
                if ($value < $this->pageSizeLimit[0]) {
                    $value = $this->pageSizeLimit[0];
                } elseif ($value > $this->pageSizeLimit[1]) {
                    $value = $this->pageSizeLimit[1];
                }
            }
            $this->pageSize = $value;
        }
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
    public function createUrl(int $page, int $pageSize = null, bool $absolute = false): string
    {
        $action = $this->urlMatcher->getCurrentRoute()->getName();

        return $this->urlGenerator->generate($action, ['page' => $page]);
    }

    /**
     * @return int the offset of the data. This may be used to set the OFFSET value for a SQL statement for fetching the
     * current page of data.
     */
    public function getOffset(): int
    {
        $pageSize = $this->getPageSize();

        return $pageSize < 1 ? 0 : $this->getPage() * $pageSize;
    }

    /**
     * @return int the limit of the data. This may be used to set the LIMIT value for a SQL statement for fetching the
     * current page of data.
     *
     * Note that if the page size is infinite, a value -1 will be returned.
     */
    public function getLimit(): int
    {
        $pageSize = $this->getPageSize();

        return $pageSize < 1 ? -1 : $pageSize;
    }

    /**
     * Returns a whole set of links for navigating to the first, last, next and previous pages.
     *
     * @param bool $absolute whether the generated URLs should be absolute.
     *
     * @return array the links for navigational purpose. The array keys specify the purpose of the links
     * (e.g. {@see LINK_FIRST}), and the array values are the corresponding URLs.
     */
    public function getLinks(bool $absolute = false): array
    {
        $currentPage = $this->getPage();
        $pageCount = $this->getPageCount();

        $links = ['self' => $this->createUrl($currentPage, null, $absolute)];

        if ($pageCount > 0) {
            $links[self::LINK_FIRST] = $this->createUrl(0, null, $absolute);
            $links[self::LINK_LAST] = $this->createUrl($pageCount - 1, null, $absolute);
            if ($currentPage > 0) {
                $links[self::LINK_PREV] = $this->createUrl($currentPage - 1, null, $absolute);
            }
            if ($currentPage < $pageCount - 1) {
                $links[self::LINK_NEXT] = $this->createUrl($currentPage + 1, null, $absolute);
            }
        }

        return $links;
    }
}
