<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Helper;

use Yiisoft\Db\Query\Query;
use Yiisoft\Strings\Inflector;

use function array_merge;
use function explode;
use function is_array;
use function strncmp;
use function substr;

/**
 * Sort represents information relevant to sorting.
 *
 * When data needs to be sorted according to one or several attributes, we can use Sort to represent the sorting
 * information and generate appropriate hyperlinks that can lead to sort actions.
 *
 * A typical usage example is as follows,
 *
 * ```php
 * public function actionIndex()
 * {
 *     $sort = new Sort();
 *
 *     $sort->attributes(
 *         [
 *             'age',
 *             'name' => [
 *                  'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
 *                  'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
 *             ]
 *         ]
 *     )->params(['sort' => 'age,-name'])->multisort();
 * }
 * ```
 *
 * In the above, we declare two {@see attributes} that support sorting: `name` and `age`.
 *
 * For more details and usage information on Sort, see the [guide article on sorting](guide:output-sorting).
 */
final class Sort
{
    /** @var array|null */
    private ?array $attributeOrders = null;
    private array $attributes = [];
    private array $defaultOrder = [];
    private array $params = [];
    private bool $multisort = false;
    private string $separator = ',';
    private string $sortParam = 'sort';

    /**
     * @param array $value list of attributes that are allowed to be sorted. Its syntax can be described using the
     * following example:
     *
     * ```php
     * [
     *     'age',
     *     'name' => [
     *         'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
     *         'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
     *         'default' => SORT_DESC,
     *         'label' => 'Name',
     *     ],
     * ]
     * ```
     *
     * In the above, two attributes are declared: `age` and `name`. The `age` attribute is a simple attribute which is
     * equivalent to the following:
     *
     * ```php
     * [
     *     'age' => [
     *         'asc' => ['age' => SORT_ASC],
     *         'desc' => ['age' => SORT_DESC],
     *     ],
     *     'default' => SORT_ASC,
     *     'label' => (new Inflector())->toHumanReadable('age');,
     * ]
     * ```
     *
     * ```php
     * 'name' => [
     *     'asc' => '[[last_name]] ASC NULLS FIRST', // PostgreSQL specific feature
     *     'desc' => '[[last_name]] DESC NULLS LAST',
     * ]
     * ```
     *
     * The `name` attribute is a composite attribute:
     *
     * - The `name` key represents the attribute name which will appear in the URLs leading to sort actions.
     * - The `asc` and `desc` elements specify how to sort by the attribute in ascending and descending orders,
     *   respectively. Their values represent the actual columns and the directions by which the data should be sorted
     *   by.
     * - The `default` element specifies by which direction the attribute should be sorted if it is not currently sorted
     *   (the default value is ascending order).
     * - The `label` element specifies what label should be used when calling {@see link()} to create a sort link.
     *   If not set, {@see Inflector::toHumanReadable()} will be called to get a label. Note that it will not be
     *   HTML-encoded.
     *
     * Note that if the Sort object is already created, you can only use the full format to configure every attribute.
     * Each attribute must include these elements: `asc` and `desc`.
     *
     * @return $this
     */
    public function attributes(array $value = []): self
    {
        $attributes = [];

        /** @var array<string,array|string> $value */
        foreach ($value as $name => $attribute) {
            if (!is_array($attribute)) {
                $attributes[$attribute] = [
                    'asc' => [$attribute => SORT_ASC],
                    'desc' => [$attribute => SORT_DESC],
                ];
            } elseif (!isset($attribute['asc'], $attribute['desc'])) {
                $attributes[$name] = array_merge([
                    'asc' => [$name => SORT_ASC],
                    'desc' => [$name => SORT_DESC],
                ], $attribute);
            } else {
                $attributes[$name] = $attribute;
            }
        }

        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Sets up the currently sort information.
     *
     * @param array $attributesOrders sort directions indexed by attribute names. Sort direction can be either
     * `SORT_ASC` for ascending order or `SORT_DESC` for descending order.
     * @param bool $validate whether to validate given attribute orders against {@see attributes}.
     *
     * If validation is enabled incorrect entries will be removed.
     *
     * {@see multiSort}.
     */
    public function attributeOrders(array $attributesOrders = [], bool $validate = true): void
    {
        if ($attributesOrders === [] || !$validate) {
            $this->attributeOrders = $attributesOrders;
        } else {
            $this->attributeOrders = [];
            /** @var array<string,int> $attributesOrders */
            foreach ($attributesOrders as $attribute => $order) {
                if (isset($this->attributes[$attribute])) {
                    $this->attributeOrders[$attribute] = $order;
                    if (!$this->multisort) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param array $defaultOrder the order that should be used when the current request does not specify any order.
     *
     * The array keys are attribute names and the array values are the corresponding sort directions.
     *
     * For example:
     *
     * ```php
     * [
     *     'name' => SORT_ASC,
     *     'created_at' => SORT_DESC,
     * ]
     * ```
     *
     * @return $this
     *
     * {@see attributeOrders}
     */
    public function defaultOrder(array $defaultOrder): self
    {
        $this->defaultOrder = $defaultOrder;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the sort direction of the specified attribute in the current request.
     *
     * @param string $attribute the attribute name.
     *
     * @return int|null Sort direction of the attribute. Can be either `SORT_ASC` for ascending order or `SORT_DESC` for
     * descending order. Null is returned if the attribute is invalid or does not need to be sorted.
     */
    public function getAttributeOrder(string $attribute): ?int
    {
        /** @var array<array-key,int> */
        $orders = $this->getAttributeOrders();

        return $orders[$attribute] ?? null;
    }

    /**
     * Returns the currently requested sort information.
     *
     * @param bool $recalculate whether to recalculate the sort directions.
     *
     * @return array sort directions indexed by attribute names. Sort direction can be either `SORT_ASC` for ascending
     * order or `SORT_DESC` for descending order.
     */
    public function getAttributeOrders(bool $recalculate = false): array
    {
        if ($this->attributeOrders === null || $recalculate) {
            $this->attributeOrders = [];

            if (isset($this->params[$this->sortParam])) {
                $sortParam = $this->parseSortParam((string) $this->params[$this->sortParam]);
                /** @var array<array-key,string> $sortParam */
                foreach ($sortParam as $attribute) {
                    $descending = false;

                    if (strncmp($attribute, '-', 1) === 0) {
                        $descending = true;
                        $attribute = substr($attribute, 1);
                    }

                    if (isset($this->attributes[$attribute])) {
                        $this->attributeOrders[$attribute] = $descending ? SORT_DESC : SORT_ASC;

                        if (!$this->multisort) {
                            return $this->attributeOrders;
                        }
                    }
                }
            }

            if (empty($this->attributeOrders) && !empty($this->defaultOrder)) {
                $this->attributeOrders = $this->defaultOrder;
            }
        }

        return $this->attributeOrders;
    }

    /**
     * Returns the columns and their corresponding sort directions.
     *
     * @param bool $recalculate whether to recalculate the sort directions.
     *
     * @return array the columns (keys) and their corresponding sort directions (values). This can be passed to
     * {@see Query::orderBy()} to construct a DB query.
     */
    public function getOrders(bool $recalculate = false): array
    {
        $attributeOrders = $this->getAttributeOrders($recalculate);

        $orders = [];

        /** @var array<string,int> $attributeOrders */
        foreach ($attributeOrders as $attribute => $direction) {
            /** @var array */
            $definition = $this->attributes[$attribute];
            /** @var array */
            $columns = $definition[$direction === SORT_ASC ? 'asc' : 'desc'];
            /** @var array<string,int>|string $columns */
            if (is_iterable($columns)) {
                foreach ($columns as $name => $dir) {
                    $orders[$name] = $dir;
                }
            } else {
                $orders[] = $columns;
            }
        }

        return $orders;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function getSortParam(): string
    {
        return $this->sortParam;
    }

    /**
     * Returns a value indicating whether the sort definition supports sorting by the named attribute.
     *
     * @param string $name the attribute name.
     *
     * @return bool whether the sort definition supports sorting by the named attribute.
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function isMultiSort(): bool
    {
        return $this->multisort;
    }

    /**
     * @param bool $multisort whether the sorting can be applied to multiple attributes simultaneously.
     *
     * Defaults to `false`, which means each time the data can only be sorted by one attribute.
     *
     * @return $this
     */
    public function multiSort(bool $multisort = true): self
    {
        $this->multisort = $multisort;

        return $this;
    }

    /**
     * @param string $value the character used to separate different attributes that need to be sorted by.
     *
     * @return $this
     */
    public function separator(string $value): self
    {
        $this->separator = $value;

        return $this;
    }

    /**
     * @param string $value the name of the parameter that specifies which attributes to be sorted in which direction.
     * Defaults to `sort`.
     *
     * @return $this
     *
     * {@see params}
     */
    public function sortParam(string $value): self
    {
        $this->sortParam = $value;

        return $this;
    }

    /**
     * @param array $value parameters (name => value) that should be used to obtain the current sort directions and to
     * create new sort URLs. If not set, `$_GET` will be used instead.
     *
     * In order to add hash to all links use `array_merge($_GET, ['#' => 'my-hash'])`.
     *
     * The array element indexed by {@see sortParam} is considered to be the current sort directions. If the element
     * does not exist, the {@see defaultOrder|default order} will be used.
     *
     * @return $this
     *
     * {@see sortParam}
     * {@see defaultOrder}
     */
    public function params(array $value): self
    {
        $this->params = $value;

        return $this;
    }

    /**
     * Parses the value of {@see sortParam} into an array of sort attributes.
     *
     * The format must be the attribute name only for ascending or the attribute name prefixed with `-` for descending.
     *
     * For example the following return value will result in ascending sort by `category` and descending sort by
     * `created_at`:
     *
     * ```php
     * [
     *     'category',
     *     '-created_at'
     * ]
     * ```
     *
     * @param string $param the value of the {@see sortParam}.
     *
     * @return array the valid sort attributes.
     */
    private function parseSortParam(string $param): array
    {
        return explode($this->separator, $param);
    }
}
