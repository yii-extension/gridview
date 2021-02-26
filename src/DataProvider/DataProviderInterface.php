<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\DataProvider;

use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;

/**
 * DataProviderInterface is the interface that must be implemented by data provider classes.
 *
 * Data providers are components that sort and paginate data, and provide them to widgets such as
 * {@see GridView}.
 *
 * For more details and usage information on DataProviderInterface, see the
 * [guide article on data providers](guide:output-data-providers).
 */
interface DataProviderInterface
{
    /**
     * The prefix to the automatically generated widget IDs.
     *
     * @param string $value
     *
     * @return $this
     *
     * {@see getId()}
     */
    public function autoIdPrefix(string $value): self;

    /**
     * Returns the data active record classes in the current page.
     *
     * @return array the list of data active record classes in the current page.
     */
    public function getARClasses(): array;

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
     * Returns the key values associated with the data active record classes.
     *
     * @return array the list of key values corresponding to {@see getARClasses|arClasses}. Each data model in
     * {@see getARClasses()|ActiveRecord} is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys(): array;

    /**
     * @return Pagination pagination object.
     */
    public function getPagination(): Pagination;

    /**
     * @return Sort the sorting object.
     */
    public function getSort(): Sort;

    /**
     * Returns the total number of data active record classes.
     *
     * When {@see getPagination|pagination} is false, this is the same as {@see getCount()|count}.
     *
     * @return int total number of possible data active record classes.
     */
    public function getTotalCount(): int;

    /**
     * Set the Id of the widget.
     *
     * @param string $value
     *
     * @return $this
     */
    public function id(string $value): self;

    /**
     * Sets the key values associated with the data active record classes.
     *
     * @param array $value the list of key values corresponding to {@see arClasses}.
     */
    public function keys(array $value): void;

    /**
     * Refreshes the data provider.
     *
     * After calling this method, if {@see getARClasses()}, {@see getKeys()} or {@see getTotalCount()} is called again,
     * they will re-execute the query and return the latest data available.
     */
    public function refresh(): void;

    /**
     * Sets the total number of data active record classes.
     *
     * @param int $value the total number of data active record classes.
     */
    public function totalCount(int $value): void;
}
