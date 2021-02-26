<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\DataProvider;

use Closure;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Strings\Inflector;

use function array_keys;
use function call_user_func;
use function count;
use function is_string;

/**
 * ActiveDataProvider implements a data provider based on {@see ActiveQuery}.
 *
 * ActiveDataProvider provides data by performing DB queries using {@see ActiveQuery}.
 *
 * The following is an example of using ActiveDataProvider to provide ActiveRecord instances:
 *
 * ```php
 * $activeQuery = new ActiveQuery(MyClass::class, $db);
 * $dataProvider = new ActiveDataProvider($db, $activeQuery);
 * ```
 *
 * And the following example shows how to use ActiveDataProvider without ActiveRecord:
 *
 * ```php
 *    $activeQuery = new ActiveQuery(MyClass::class, $db);
 *
 *    $provider = new ActiveDataProvider(
 *        $activeQuery->from('order')->orderBy('id')
 *    );
 * ```
 *
 * For more details and usage information on ActiveDataProvider,
 * see the [guide article on data providers](guide:output-data-providers).
 */
final class ActiveDataProvider extends DataProvider
{
    /**
     * @var ActiveQuery the query that is used to fetch data active record class and {@see totalCount}
     *
     * if it is not explicitly set.
     */
    private ActiveQuery $activeQuery;
    /**
     * @var string|callable the column that is used as the key of the data active record class.
     *
     * This can be either a column name, or a callable that returns the key value of a given data active record class.
     *
     * If this is not set, the following rules will be used to determine the keys of the data active record class:
     *
     * - If {@see query} is an {@see ActiveQuery} instance, the primary keys of {@see ActiveQuery::arClass} will be
     *   used.
     *
     * - Otherwise, the keys of the {@see ActiveRecord} array will be used.
     *
     * @see getKeys()
     */
    private $key;
    private Sort $sort;

    public function __construct(ActiveQuery $activeQuery)
    {
        $this->activeQuery = $activeQuery;
        $this->sort = $this->getSort();

        parent::__construct();
    }

    public function sortParams(array $sortParams = []): void
    {
        /** @var ActiveRecord $arClass */
        $arClass = $this->activeQuery->getARInstance();

        /** @var array<array-key,string> $attributes */
        $attributes = array_keys($arClass->getAttributes());

        $sortAttribute = [];

        foreach ($attributes as $attribute) {
            $sortAttribute[$attribute] = [
                'asc' => [$attribute => SORT_ASC],
                'desc' => [$attribute => SORT_DESC],
                'label' => (new Inflector())->toHumanReadable($attribute),
            ];
        }

        $this->sort->attributes($sortAttribute)->params($sortParams)->multiSort();
    }

    /**
     * Prepares the data active record class that will be made available in the current page.
     *
     * @throws InvalidConfigException
     *
     * @return array the available data active record class.
     */
    protected function prepareARClass(): array
    {
        $activeQuery = clone $this->activeQuery;

        $pagination = $this->getPagination();

        $pagination->totalCount($this->getTotalCount());

        if ($pagination->getTotalCount() === 0) {
            return [];
        }

        $activeQuery->limit($pagination->getLimit())->offset($pagination->getOffset());
        $activeQuery->addOrderBy($this->sort->getOrders());

        return $activeQuery->all();
    }

    /**
     * Prepares the keys associated with the currently available data active record class.
     *
     * @param array $arClasses the available data active record class.
     *
     * @return array the keys.
     */
    protected function prepareKeys(array $arClasses = []): array
    {
        $keys = [];

        if (isset($this->key)) {
            /** @var array<array-key,array> $arClasses */
            foreach ($arClasses as $arClass) {
                if (is_string($this->key)) {
                    /** @var mixed */
                    $keys[] = $arClass[$this->key];
                } else {
                    /** @var mixed */
                    $keys[] = call_user_func($this->key, $arClass);
                }
            }

            return $keys;
        }

        $arClass = $this->activeQuery->getARInstance();
        $pks = $arClass->primaryKey();

        if (count($pks) === 1) {
            /** @var string */
            $pk = $pks[0];
            /** @var array<array-key,array> $arClasses */
            foreach ($arClasses as $arClass) {
                /** @var string */
                $keys[] = $arClass[$pk];
            }
        } else {
            /** @var array<array-key,array> $arClasses */
            foreach ($arClasses as $arClass) {
                $kk = [];
                /** @var array<array-key,string> $pks */
                foreach ($pks as $pk) {
                    /** @var string */
                    $kk[$pk] = $arClass[$pk];
                }
                $keys[] = $kk;
            }
        }

        return $keys;
    }

    /**
     * Returns a value indicating the total number of data active record class in this data provider.
     *
     * @throws InvalidConfigException
     *
     * @return int total number of data active record class in this data provider.
     */
    protected function prepareTotalCount(): int
    {
        $activeQuery = clone $this->activeQuery;

        return (int) $activeQuery->limit(-1)->offset(-1)->orderBy([])->count();
    }
}
