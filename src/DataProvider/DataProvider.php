<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\DataProvider;

use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;

/**
 * DataProvider provides a base class that implements the {@see DataProviderInterface}.
 *
 * For more details and usage information on DataProvider, see the
 * [guide article on data providers](guide:output-data-providers).
 */
abstract class DataProvider implements DataProviderInterface
{
    private string $id = '';
    private array $keys = [];
    private array $arClasses = [];
    private int $totalCount = 0;
    private bool $autoGenerate = true;
    private string $autoIdPrefix = 'dp';
    private static int $counter = 0;
    private Pagination $pagination;
    private Sort $sort;

    public function __construct()
    {
        if (isset($this->id)) {
            $this->id = $this->getId();
        }
    }

    /**
     * Prepares the data active record class that will be made available in the current page.
     *
     * @return array the available data active record class.
     */
    abstract protected function prepareARClass(): array;

    /**
     * Prepares the keys associated with the currently available data active record class.
     *
     * @param array $arClasses the available data active record class.
     *
     * @return array the keys.
     */
    abstract protected function prepareKeys(array $arClasses = []): array;

    /**
     * Returns a value indicating the total number of data active record class in this data provider.
     *
     * @return int total number of data active record class in this data provider.
     */
    abstract protected function prepareTotalCount(): int;

    /**
     * The prefix to the automatically generated widget IDs.
     *
     * @param string $value
     *
     * @return $this
     *
     * {@see getId()}
     */
    public function autoIdPrefix(string $value): self
    {
        $this->autoIdPrefix = $value;
        return $this;
    }

    /**
     * Sets the data active record classes in the current page.
     *
     * @param array $value the active record clasess in the current page.
     */
    public function arClasses(array $value): void
    {
        $this->arClasses = $value;
    }

    /**
     * Counter used to generate {@see id} for widgets.
     *
     * @param int $value
     */
    public static function counter(int $value): void
    {
        self::$counter = $value;
    }

    /**
     * Returns the data active record classes in the current page.
     *
     * @return array the list of data active record classes in the current page.
     */
    public function getARClasses(): array
    {
        $this->prepare();

        return $this->arClasses;
    }

    /**
     * Returns the number of data active record classes in the current page.
     *
     * @return int the number of data active record classes in the current page.
     */
    public function getCount(): int
    {
        return count($this->getARClasses());
    }

    /**
     * @return string Id of the widget.
     */
    protected function getId(): string
    {
        if ($this->autoGenerate) {
            $this->id = $this->autoIdPrefix . ++self::$counter;
        }

        return $this->id;
    }

    /**
     * Returns the pagination object used by this data provider.
     *
     * @return Pagination the pagination object.
     */
    public function getPagination(): Pagination
    {
        if (!isset($this->pagination)) {
            $this->pagination = new Pagination();
        }

        return $this->pagination;
    }

    /**
     * Returns the sorting object used by this data provider.
     *
     * @return Sort the sorting object.
     */
    public function getSort(): Sort
    {
        if (!isset($this->sort)) {
            $this->sort = new Sort();
        }

        return $this->sort;
    }

    /**
     * Returns the total number of data active record classes.
     *
     * When {@see pagination} is false, this returns the same value as {@see count}. Otherwise, it will call
     * {@see prepareTotalCount()} to get the count.
     *
     * @return int total number of possible data active record clasess.
     */
    public function getTotalCount(): int
    {
        if ($this->totalCount === 0) {
            $this->totalCount = $this->prepareTotalCount();
        }

        return $this->totalCount;
    }

    /**
     * Returns the key values associated with the data active record classes.
     *
     * @return array the list of key values corresponding to {@see arClasses}. Each data active record class in
     * {@see arClasses} is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys(): array
    {
        $this->prepare();

        return $this->keys;
    }

    /**
     * Set the Id of the widget.
     *
     * @param string $value
     *
     * @return $this
     */
    public function id(string $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Sets the key values associated with the data active record classes.
     *
     * @param array $value the list of key values corresponding to {@see arClasses}.
     */
    public function keys(array $value): void
    {
        $this->keys = $value;
    }

    /**
     * Refreshes the data provider.
     *
     * After calling this method, if {@see getARClasses()}, {@see getKeys()} or {@see getTotalCount()} is called again,
     * they will re-execute the query and return the latest data available.
     */
    public function refresh(): void
    {
        $this->totalCount = 0;
        $this->arClasses = [];
        $this->keys = [];
    }

    /**
     * Sets the total number of data active record classes.
     *
     * @param int $value the total number of data active record classes.
     */
    public function totalCount(int $value): void
    {
        $this->totalCount = $value;
    }

    /**
     * Prepares the data active record class and keys.
     *
     * This method will prepare the data active record class and keys that can be retrieved via {@see getARClasses()}
     * and {@see getKeys()}.
     *
     * This method will be implicitly called by {@see getArClasses} and {@see getKeys()} if it has not been called
     * before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    private function prepare(bool $forcePrepare = false): void
    {
        if ($forcePrepare || $this->arClasses === []) {
            $this->arClasses = $this->prepareARClass();
        }

        if ($forcePrepare || $this->keys === []) {
            $this->keys = $this->prepareKeys($this->arClasses);
        }
    }
}
