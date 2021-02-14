<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\DataProvider;

use Yii\Extension\GridView\Helper\Sort;
use Yiisoft\Arrays\ArrayHelper;

/**
 * ArrayDataProvider implements a data provider based on a data array.
 *
 * The {@see allData} property contains all data models that may be sorted and/or paginated.
 *
 * ArrayDataProvider will provide the data after sorting and/or pagination.
 * You may configure the {@see sort} and {@see pagination} properties to customize the sorting and pagination behaviors.
 *
 * Elements in the {@see allData} array may be either objects (e.g. model objects) or associative arrays (e.g. query
 * results of DAO).
 *
 * Make sure to set the {@see key} property to the name of the field that uniquely identifies a data record or false if
 * you do not have such a field.
 *
 * Compared to {@see ActiveDataProvider}, ArrayDataProvider could be less efficient because it needs to have
 * {@seeallData} ready.
 *
 * ArrayDataProvider may be used in the following way:
 *
 * ```php
 * $query = new Query($db);
 * $provider = (new ArrayDataProvider())
 *     ->allData($query->from('post')->all()),
 *     ->sort() => (new sort())->attributes(['id', 'username'])->params($sortParams)->enableMultiSort(true),
 *     ->pagination() => $pagination;
 *
 * // get the posts in the current page
 * $posts = $provider->getModels();
 * ```
 *
 * Note: if you want to use the sorting feature, you must configure the [[sort]] property
 * so that the provider knows which columns can be sorted.
 *
 * For more details and usage information on ArrayDataProvider, see the [guide article on data providers](guide:output-data-providers).
 */
final class ArrayDataProvider extends DataProvider
{
    /** @var string|callable */
    public $key;
    public array $allData;

    /**
     * @var string|callable $key the column that is used as the key of the data.
     *
     * This can be either a column name, or a callable that returns the key value of a given data model.
     * If this is not set, the index of the data array will be used.
     *
     * {@see getKeys()}
     */
    public function key($key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @var array the data that is not paginated or sorted. When pagination is enabled, this property usually contains
     * more elements.
     *
     * The array elements must use zero-based integer keys.
     */
    public function allData(array $allData): self
    {
        $this->allData = $allData;

        return $this;
    }

    protected function prepareARClass(): array
    {
        $arClass = $this->allData;

        if ($arClass === null) {
            return [];
        }

        $sort = $this->getSort();

        if ($sort !== null) {
            $arClass = $this->sortModels($arClass, $sort);
        }

        $pagination = $this->getPagination();
        $pagination->totalCount($this->getTotalCount());

        if ($pagination->getPageSize() > 0) {
            $arClass = array_slice($arClass, $pagination->getOffset(), $pagination->getLimit(), true);
        }

        return $arClass;
    }

    protected function prepareKeys(array $arClasses = []): array
    {
        if ($this->key !== null) {
            $keys = [];
            foreach ($arClasses as $arClass) {
                if (is_string($this->key)) {
                    $keys[] = $arClass[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $arClass);
                }
            }

            return $keys;
        }

        return array_keys($arClasses);
    }

    protected function prepareTotalCount(): int
    {
        return is_array($this->allData) ? count($this->allData) : 0;
    }

    protected function sortModels(array $arClass, Sort $sort): array
    {
        $orders = $sort->getOrders();

        if (!empty($orders)) {
            ArrayHelper::multisort($models, array_keys($orders), array_values($orders), $sort->sortFlags);
        }

        return $models;
    }
}
