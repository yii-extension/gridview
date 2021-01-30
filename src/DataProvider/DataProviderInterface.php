<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\DataProvider;

use Yii\Extension\GridView\Pagination;
use Yii\Extension\GridView\Sort;

/**
 * DataProviderInterface is the interface that must be implemented by data provider classes.
 *
 * Data providers are components that sort and paginate data, and provide them to widgets such as
 * {@see Yiisoft\Yii\DataView\GridView}, {@see Yiisoft\Yii\DataView\ListView}.
 *
 * For more details and usage information on DataProviderInterface, see the
 * [guide article on data providers](guide:output-data-providers).
 */
interface DataProviderInterface
{
    /**
     * Prepares the data active record classes and keys.
     *
     * This method will prepare the data active record classes and keys that can be retrieved via {@see getARClasses()}
     * {@see getKeys()}.
     *
     * This method will be implicitly called by {@see getARClasses()} and {@see getKeys()} if it has not been called
     * before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    public function prepare(bool $forcePrepare = false): void;

    /**
     * Returns the number of data active record classes in the current page.
     *
     * This is equivalent to `count($provider->getARClasses())`.
     *
     * When {@see getPagination()|pagination} is false, this is the same as {@see getTotalCount()|totalCount}.
     *
     * @return int the number of data active record classes in the current page.
     */
    public function getCount(): int;

    /**
     * Returns the total number of data active record classes.
     *
     * When {@see getPagination|pagination} is false, this is the same as {@see getCount()|count}.
     *
     * @return int total number of possible data active record classes.
     */
    public function getTotalCount(): int;

    /**
     * Returns the data active record classes in the current page.
     *
     * @return array the list of data active record classes in the current page.
     */
    public function getARClasses(): array;

    /**
     * Returns the key values associated with the data active record classes.
     *
     * @return array the list of key values corresponding to {@see getARClasses|arClasses}. Each data model in
     * {@see getARClasses()|ActiveRecord} is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys(): array;

    /**
     * @return Sort|null the sorting object. If this is false, it means the sorting is disabled.
     */
    public function getSort(): ?Sort;

    /**
     * @return Pagination|null pagination object. If this is false, it means the pagination is disabled.
     */
    public function getPagination(): ?Pagination;
}
