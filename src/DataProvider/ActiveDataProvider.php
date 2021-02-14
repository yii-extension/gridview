<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\DataProvider;

use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\QueryInterface;

use function array_keys;
use function call_user_func;
use function count;
use function is_string;

/**
 * ActiveDataProvider implements a data provider based on {@see \Yiisoft\Db\Query\Query} and
 * {@see \Yiisoft\ActiveRecord\ActiveQuery}.
 *
 * ActiveDataProvider provides data by performing DB queries using {@see query}.
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
 *    $query = new Query($db);
 *
 *    $provider = new ActiveDataProvider(
 *        $db, // connection db
 *        $query->from('order')->orderBy('id')
 *    );
 * ```
 *
 * For more details and usage information on ActiveDataProvider, see the
 * [guide article on data providers](guide:output-data-providers).
 */
final class ActiveDataProvider extends DataProvider
{
    /**
     * @var QueryInterface|null the query that is used to fetch data active record class and {@see totalCount}
     *
     * if it is not explicitly set.
     */
    private ?QueryInterface $query = null;

    /**
     * @var string|callable the column that is used as the key of the data active record class.
     *
     * This can be either a column name, or a callable that returns the key value of a given data active record class.
     *
     * If this is not set, the following rules will be used to determine the keys of the data active record class:
     *
     * - If {@see query} is an {@see \Yiisoft\ActiveRecord\ActiveQuery} instance, the primary keys of
     * {@see ActiveQuery::arClass} will be used.
     *
     * - Otherwise, the keys of the {@see ActiveRecord} array will be used.
     *
     * @see getKeys()
     */
    private $key;

    public function __construct(QueryInterface $query)
    {
        $this->query = $query;

        parent::__construct();
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

        if ($this->key !== null) {
            foreach ($arClasses as $arClass) {
                if (is_string($this->key)) {
                    $keys[] = $arClass[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $arClass);
                }
            }

            return $keys;
        }

        if ($this->query instanceof ActiveQueryInterface) {
            /* @var $class ActiveRecordInterface */
            $arClass = $this->query->getARInstance();

            $pks = $arClass->primaryKey();

            if (count($pks) === 1) {
                $pk = $pks[0];
                foreach ($arClasses as $arClass) {
                    $keys[] = $arClass[$pk];
                }
            } else {
                foreach ($arClasses as $arClass) {
                    $kk = [];
                    foreach ($pks as $pk) {
                        $kk[$pk] = $arClass[$pk];
                    }
                    $keys[] = $kk;
                }
            }

            return $keys;
        }

        return array_keys($arClasses);
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
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException(
                'The "query" property must be an instance of a class that implements the QueryInterface e.g.'
                    . '\Yiisoft\Db\Query\Query or its subclasses.'
            );
        }

        $query = clone $this->query;

        if (($pagination = $this->getPagination()) !== null) {
            $pagination->totalCount($this->getTotalCount());
            if ($pagination->getTotalCount() === 0) {
                return [];
            }

            $query->limit($pagination->getLimit())->offset($pagination->getOffset());
        }

        $sort = $this->getSort();

        if ($sort !== null) {
            $query->addOrderBy($sort->getOrders());
        }

        return $query->all();
    }

    public function withSort(Sort $value): void
    {
        parent::withSort($value);

        if ($this->query instanceof ActiveQueryInterface && ($sort = $this->getSort()) !== null) {
            /* @var $class ActiveRecordInterface */
            $arClass = $this->query->getARInstance();

            if (empty($sort->attributes)) {
                foreach ($arClass->getAttributes() as $attribute) {
                    $sort->attributes[$attribute] = [
                        'asc' => [$attribute => SORT_ASC],
                        'desc' => [$attribute => SORT_DESC],
                    ];
                }
            }
        }
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
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException(
                'The "query" property must be an instance of a class that implements the QueryInterface e.g. '
                . '\Yiisoft\Db\Query\Query or its subclasses.'
            );
        }

        $query = clone $this->query;

        return (int) $query->limit(-1)->offset(-1)->orderBy([])->count('*');
    }
}
