<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\DataProvider;

use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;
use Yiisoft\Arrays\ArraySorter;

/**
 * ArrayDataProvider implements a data provider based on a data array.
 *
 * The {@see allData} property contains all data models that may be sorted and/or paginated.
 *
 * ArrayDataProvider will provide the data after sorting and/or pagination.
 *
 * You may configure the {@see sort} and {@see pagination} properties to customize the sorting and pagination behaviors.
 *
 * Elements in the {@see allData} array may be either objects (e.g. model objects) or associative arrays (e.g. query
 * results of DAO).
 *
 * Make sure to set the {@see key} property to the name of the field that uniquely identifies a data record or false if
 * you do not have such a field.
 *
 * Compared to {@see ActiveDataProvider}, ArrayDataProvider could be less efficient because it needs to have
 * {@see allData} ready.
 *
 * ArrayDataProvider may be used in the following way:
 *
 * ```php
 * $query = new Query($db);
 * $provider = (new ArrayDataProvider())->allData($query->from('post')->all());
 *
 * // get the posts in the current page
 * $posts = $provider->getModels();
 * ```
 *
 * Note: if you want to use the sorting feature, you must configure the {@see sort} property so that the provider knows
 * which columns can be sorted.
 *
 * For more details and usage information on ArrayDataProvider,
 * see the [guide article on data providers](guide:output-data-providers).
 */
final class ArrayDataProvider extends DataProvider
{
    /** @var array<array-key,array> */
    private array $allData;
    /** @var callable|string */
    private $key;
    private Sort $sort;

    public function __construct()
    {
        $this->sort = $this->getSort();

        parent::__construct();
    }

    /**
     * @param array<array-key,array> $allData the data that is not paginated or sorted. When pagination is enabled, this
     * property usually contains more elements.
     *
     * The array elements must use zero-based integer keys.
     *
     * @return $this
     */
    public function allData(array $allData): self
    {
        $this->allData = $allData;

        return $this;
    }

    /**
     * @param callable|string $key the column that is used as the key of the data.
     *
     * This can be either a column name, or a callable that returns the key value of a given data model.
     *
     * If this is not set, the index of the data array will be used.
     *
     * @return $this
     *
     * {@see getKeys()}
     */
    public function key($key): self
    {
        if (!is_string($key) && !is_callable($key)) {
            throw new InvalidConfigException('The property "key" must be of type "string" or "callable".');
        }

        $this->key = $key;

        return $this;
    }

    protected function prepareARClass(): array
    {
        $arClass = $this->allData;

        if ($arClass === []) {
            return [];
        }

        $arClass = $this->sortModels($arClass);

        $pagination = $this->getPagination();
        $pagination->totalCount($this->getTotalCount());

        if ($pagination->getPageSize() > 0) {
            $arClass = array_slice($arClass, $pagination->getOffset(), $pagination->getLimit(), true);
        }

        return $arClass;
    }

    protected function prepareKeys(array $arClasses = []): array
    {
        if (isset($this->key)) {
            $keys = [];
            /** @var array<array-key, mixed> */
            foreach ($arClasses as $arClass) {
                if (is_string($this->key)) {
                    /** @var mixed */
                    $keys[] = $arClass[$this->key];
                } else {
                    /** @var mixed */
                    $keys[] = ($this->key)($arClass);
                }
            }

            return $keys;
        }

        return array_keys($arClasses);
    }

    protected function prepareTotalCount(): int
    {
        return count($this->allData);
    }

    /**
     * @param array<array-key,array> $arClasses
     */
    protected function sortModels(array $arClasses): array
    {
        $orders = $this->sort->getOrders();

        /** @var array<array-key, string> */
        $keys = array_keys($orders);

        /** @var array<array-key, int> */
        $direction = array_values($orders);

        if ($orders !== []) {
            ArraySorter::multisort($arClasses, $keys, $direction);
        }

        return $arClasses;
    }
}
