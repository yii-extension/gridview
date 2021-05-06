<?php

declare(strict_types=1);

namespace Yii\Extension\GridView;

use Closure;
use JsonException;
use Yii\Extension\GridView\Column\ActionColumn;
use Yii\Extension\GridView\Column\Column;
use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Widget\BaseListView;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Json\Json;

/**
 * The GridView widget is used to display data in a grid.
 *
 * It provides features like {@see sorter|sorting}, {@see pager|paging} and also {@see filterModel|filtering} the data.
 *
 * A basic usage looks like the following:
 *
 * ```php
 * ```
 *
 * The columns of the grid table are configured in terms of {@see Column} classes, which are configured via
 * {@see columns}.
 *
 * The look and feel of a grid view can be customized using the large amount of properties.
 *
 * For more details and usage information on GridView, see the:
 * [guide article on data widgets](guide:output-data-widgets).
 */
final class GridView extends BaseListView
{
    public const FILTER_POS_HEADER = 'header';
    public const FILTER_POS_FOOTER = 'footer';
    public const FILTER_POS_BODY = 'body';
    protected string $layout = "{header}\n{toolbar}\n{items}\n{summary}\n{pager}";
    protected array $options = ['class' => 'grid-view'];
    private ?Closure $afterRow = null;
    private ?Closure $beforeRow = null;
    private string $filterModelName = '';
    private string $header = '';
    private array $headerOptions = [];
    /** @var array<array-key,array<array-key,Column>|Column|string> */
    private array $columns = [];
    private string $dataColumnClass = DataColumn::class;
    private array $filterRowOptions = ['class' => 'filters'];
    private array $footerRowOptions = [];
    private array $headerRowOptions = [];
    private array $rowOptions = [];
    private array $tableOptions = ['class' => 'table'];
    private bool $showFooter = false;
    private bool $showHeader = true;
    /** @var array<array-key,array> */
    private array $toolbar = [];
    private array $toolbarOptions = [];
    private string $emptyCell = '&nbsp;';
    private string $filterPosition = self::FILTER_POS_BODY;

    protected function run(): string
    {
        if (!isset($this->dataProvider)) {
            throw new InvalidConfigException('The "dataProvider" property must be set.');
        }

        if ($this->emptyText !== '') {
            $this->emptyText = $this->translator->translate($this->emptyText, [], 'yii-gridview');
        }

        if (!isset($this->options['id'])) {
            $this->options['id'] = "{$this->getId()}-gridview";
        }

        $this->initColumns();

        return parent::run();
    }

    /**
     * @param Closure|null $afterRow an anonymous function that is called once AFTER rendering each data arClass.
     *
     * It should have the similar signature as {@see rowOptions}. The return result of the function will be rendered
     * directly.
     *
     * @return $this
     */
    public function afterRow(?Closure $afterRow): self
    {
        $new = clone $this;
        $new->afterRow = $afterRow;

        return $new;
    }

    /**
     * @param Closure|null $beforeRow an anonymous function that is called once BEFORE rendering each data arClass.
     *
     * It should have the similar signature as {@see rowOptions}. The return result of the function will be rendered
     * directly.
     *
     * @return $this
     */
    public function beforeRow(?Closure $beforeRow): self
    {
        $new = clone $this;
        $new->beforeRow = $beforeRow;

        return $new;
    }

    /**
     * @param array $columns grid column configuration. Each array element represents the configuration for one
     * particular grid column. For example,
     *
     * ```php
     * [
     *     [
     *         'class' => SerialColumn::class,
     *     ],
     *     [
     *         'class' => DataColumn::class, // this line is optional
     *         'attribute()' => ['name'],
     *         'format()' => ['text'],
     *         'label()' => ['Name'],
     *     ],
     *     [
     *         'class' => CheckboxColumn::class,
     *     ],
     * ]
     * ```
     *
     * If a column is of class {@see DataColumn}, the "class" element can be omitted.
     *
     * As a shortcut format, a string may be used to specify the configuration of a data column which only contains
     * {@see DataColumn::attribute|attribute}, {@see DataColumn::format|format}, and/or {@see DataColumn::label|label}
     * options: `"attribute:format:label"`.
     *
     * For example, the above "name" column can also be specified as: `"name:text:Name"`.
     *
     * Both "format" and "label" are optional. They will take default values if absent.
     *
     * Using the shortcut format the configuration for columns in simple cases would look like this:
     *
     * ```php
     * [
     *     'id',
     *     'amount:currency:Total Amount',
     *     'created_at:datetime',
     * ]
     * ```
     *
     * When using a {@see dataProvider} with active records, you can also display values from related records, e.g. the
     * `name` attribute of the `author` relation:
     *
     * ```php
     * // shortcut syntax
     * 'author.name',
     * // full syntax
     * [
     *     'attribute()' => ['author.name'],
     *     // ...
     * ]
     * ```
     * @psalm-param array<array-key,array<array-key,Column>|Column|string> $columns
     */
    public function columns(array $columns): self
    {
        $new = clone $this;
        $new->columns = $columns;

        return $new;
    }

    /**
     * @param string the default data column class if the class name is not explicitly specified when configuring a data
     * column.
     *
     * @return $this
     */
    public function dataColumnClass(string $dataColumnClass): self
    {
        $new = clone $this;
        $new->dataColumnClass = $dataColumnClass;

        return $new;
    }

    /**
     * @param string $emptyCell the HTML display when the content of a cell is empty. This property is used to render
     * cells that have no defined content, e.g. empty footer or filter cells.
     */
    public function emptyCell(string $emptyCell): self
    {
        $new = clone $this;
        $new->emptyCell = $emptyCell;

        return $new;
    }

    /**
     * @param string $filterPosition whether the filters should be displayed in the grid view. Valid values include:
     *
     * - {@see FILTER_POS_HEADER}: the filters will be displayed on top of each column's header cell.
     * - {@see FILTER_POS_BODY}: the filters will be displayed right below each column's header cell.
     * - {@see FILTER_POS_FOOTER}: the filters will be displayed below each column's footer cell.
     */
    public function filterPosition(string $filterPosition): self
    {
        $new = clone $this;
        $new->filterPosition = $filterPosition;

        return $new;
    }

    /**
     * @param string the form model name that keeps the user-entered filter data. When this property is set, the grid
     * view will enable column-based filtering. Each data column by default will display a text field at the top that
     * users can fill in to filter the data.
     *
     * Note that in order to show an input field for filtering, a column must have its {@see DataColumn::attribute}
     * property set and the attribute should be active in the current scenario of $filterModelName or have
     * {@see DataColumn::filter} set as the HTML code for the input field.
     */
    public function filterModelName(string $filterModelName): self
    {
        $new = clone $this;
        $new->filterModelName = $filterModelName;

        return $new;
    }

    /**
     * @param array the HTML attributes for the filter row element.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function filterRowOptions(array $filterRowOptions): self
    {
        $new = clone $this;
        $new->filterRowOptions = $filterRowOptions;

        return $new;
    }

    /**
     * @param array $footerRowOptions the HTML attributes for the table footer row.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function footerRowOptions(array $footerRowOptions)
    {
        $new = clone $this;
        $new->footerRowOptions = $footerRowOptions;

        return $new;
    }

    /**
     * @param string $header the header of the grid table.
     *
     * @return $this
     *
     * {@see headerOptions}
     */
    public function header(string $header)
    {
        $new = clone $this;
        $new->header = $header;

        return $new;
    }

    /**
     * @param array $headerOptions the HTML attributes for the caption element.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     * {@see caption}
     */
    public function headerOptions(array $headerOptions): self
    {
        $new = clone $this;
        $new->headerOptions = $headerOptions;

        return $new;
    }

    /**
     * @param array $headerRowOptions the HTML attributes for the table header row.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function headerRowOptions(array $headerRowOptions): self
    {
        $new = clone $this;
        $new->headerRowOptions = $headerRowOptions;

        return $new;
    }

    public function getEmptyCell(): string
    {
        return $this->emptyCell;
    }

    public function getfilterModelName(): string
    {
        return $this->filterModelName;
    }

    /**
     * Whether not show the header section of the grid table.
     */
    public function notShowHeader(): self
    {
        $new = clone $this;
        $new->showHeader = false;

        return $new;
    }

    /**
     * @param array $rowOptions the HTML attributes for the table body rows.
     *
     * This can be either an array specifying the common HTML attributes for all body rows.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function rowOptions(array $rowOptions): self
    {
        $new = clone $this;
        $new->rowOptions = $rowOptions;

        return $new;
    }

    /**
     * Whether to show the footer section of the grid table.
     */
    public function showFooter(): self
    {
        $new = clone $this;
        $new->showFooter = true;

        return $new;
    }

    /**
     * @param array $tableOptions the HTML attributes for the grid table element.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function tableOptions(array $tableOptions): self
    {
        $new = clone $this;
        $new->tableOptions = $tableOptions;

        return $new;
    }

    /**
     * @psalm-param array<array-key,array> $toolbar
     */
    public function toolbar(array $toolbar): self
    {
        $new = clone $this;
        $new->toolbar = $toolbar;

        return $new;
    }

    public function toolbarOptions(array $toolbarOptions): self
    {
        $new = clone $this;
        $new->toolbarOptions = $toolbarOptions;

        return $new;
    }

    protected function renderSection(string $name): string
    {
        switch ($name) {
            case '{header}':
                return $this->renderHeader();
            case '{toolbar}':
                return $this->renderToolbar();
            default:
                return parent::renderSection($name);
        }
    }

    /**
     * Renders the data active record classes for the grid view.
     *
     * @throws JsonException
     *
     * @return string the HTML code of table
     */
    protected function renderItems(): string
    {
        $columnGroup = $this->renderColumnGroup();
        $tableHeader = $this->showHeader ? $this->renderTableHeader() : false;
        $tableBody = $this->renderTableBody();

        $tableFooter = false;

        if ($this->showFooter) {
            $tableFooter = $this->renderTableFooter();
        }

        $content = array_filter([
            $columnGroup,
            $tableHeader,
            $tableFooter,
            $tableBody,
        ]);

        return $this->html->tag('table', implode("\n", $content), $this->tableOptions);
    }

    /**
     * Creates a {@see DataColumn} object based on a string in the format of "attribute:format:label".
     *
     * @param string $text the column specification string
     *
     * @throws InvalidConfigException if the column specification is invalid
     *
     * @return Column the column instance
     */
    private function createDataColumn($text): Column
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException(
                'The column must be specified in the format of "attribute", "attribute:format" or ' .
                '"attribute:format:label"'
            );
        }

        /** @var DataColumn $dataColumn */
        $dataColumn = $this->gridViewFactory->createColumnClass(
            ['class' => $this->dataColumnClass, 'grid()' => [$this]]
        );

        $dataColumn->attribute($matches[1]);
        $dataColumn->format($matches[3] ?? 'text');
        $dataColumn->label($matches[5] ?? '');

        return $dataColumn;
    }

    /**
     * This function tries to guess the columns to show from the given data if {@see columns} are not explicitly
     * specified.
     */
    private function guessColumns(): void
    {
        $arClasses = $this->dataProvider->getARClasses();

        reset($arClasses);

        /** @var array<array-key,object|int|bool|string|null> $arClasses */
        foreach ($arClasses as $name => $value) {
            if ($value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                $this->columns[] = (string) $name;
            }
        }
    }

    /**
     * Creates column objects and initializes them.
     */
    private function initColumns(): void
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }

        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } elseif (is_array($column)) {
                $buttons = $this->checkColumnButtonFunctions($column);
                $content = $this->checkColumnContentFunctions($column);
                $value = $this->checkColumnValueFunctions($column);
                $visibleButtons = $this->checkColumnVisibleButtonsFunctions($column);

                unset($column['buttons'], $column['content'], $column['value'], $column['visibleButtons']);

                $config = array_merge(
                    [
                        'class' => $this->dataColumnClass,
                        'grid()' => [$this],
                    ],
                    $column,
                );

                $column = $this->gridViewFactory->createColumnClass($config);

                if ($column instanceof ActionColumn && $buttons !== null) {
                    $column->buttons($buttons);
                }

                if ($content !== null) {
                    $column->content($content);
                }

                if ($column instanceof DataColumn && $value !== null) {
                    $column->value($value);
                }

                if ($column instanceof ActionColumn && $visibleButtons !== null) {
                    $column->visibleButtons($visibleButtons);
                }
            }

            if (!$column->isVisible()) {
                unset($this->columns[$i]);
                continue;
            }

            $this->columns[$i] = $column;
        }
    }

    /**
     * Renders the caption element.
     *
     * @throws JsonException
     *
     * @return string the rendered caption element or `` if no caption element should be rendered.
     */
    private function renderHeader(): string
    {
        if (!empty($this->header)) {
            return $this->html->tag('header', $this->header, $this->headerOptions);
        }

        return '';
    }

    /**
     * Renders the column group HTML.
     *
     * @throws JsonException
     *
     * @return string the column group HTML or `` if no column group should be rendered.
     */
    private function renderColumnGroup(): string
    {
        foreach ($this->columns as $column) {
            if ($column instanceof Column && $column->getOptions() !== []) {
                $cols = [];
                foreach ($this->columns as $col) {
                    if ($col instanceof Column) {
                        $cols[] = $this->html->tag('col', '', $col->getOptions());
                    }
                }

                return $this->html->tag('colgroup', implode("\n", $cols));
            }
        }

        return '';
    }

    /**
     * Renders the filter.
     *
     * @return string the rendering result.
     */
    private function renderFilters(): string
    {
        if ($this->filterModelName !== '') {
            $cells = [];

            foreach ($this->columns as $column) {
                if ($column instanceof Column) {
                    $cells[] = $column->renderFilterCell();
                }
            }

            return $this->html->tag('tr', implode('', $cells), $this->filterRowOptions);
        }

        return '';
    }

    /**
     * Renders the table body.
     *
     * @return string the rendering result.
     */
    private function renderTableBody(): string
    {
        $arClasses = array_values($this->dataProvider->getARClasses());

        $keys = $this->dataProvider->getKeys();
        $rows = [];

        /** @var array<int,activeRecord|array> $arClasses */
        foreach ($arClasses as $index => $arClass) {
            /** @var mixed */
            $key = isset($keys[$index]) ? $keys[$index] : $index;
            if ($this->beforeRow !== null) {
                /** @var array */
                $row = call_user_func($this->beforeRow, $arClass, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }

            $rows[] = $this->renderTableRow($arClass, $key, $index);

            if ($this->afterRow !== null) {
                /** @var array */
                $row = call_user_func($this->afterRow, $arClass, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        if (empty($rows) && $this->emptyText !== '') {
            $colspan = count($this->columns);

            return "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        }

        return "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
    }

    /**
     * Renders the table footer.
     *
     * @return string the rendering result.
     */
    private function renderTableFooter(): string
    {
        $cells = [];

        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                $cells[] = $column->renderFooterCell();
            }
        }

        $content = $this->html->tag('tr', implode('', $cells), $this->footerRowOptions);

        if ($this->filterPosition === self::FILTER_POS_FOOTER) {
            $content .= $this->renderFilters();
        }

        return "<tfoot>\n" . $content . "\n</tfoot>";
    }

    /**
     * Renders the table header.
     *
     * @return string the rendering result.
     */
    private function renderTableHeader(): string
    {
        $cells = [];

        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                $cells[] = $column->renderHeaderCell();
            }
        }

        $content = $this->html->tag('tr', implode('', $cells), $this->headerRowOptions);

        if ($this->filterPosition === self::FILTER_POS_HEADER) {
            $content = $this->renderFilters() . $content;
        } elseif ($this->filterPosition === self::FILTER_POS_BODY) {
            $content .= $this->renderFilters();
        }

        return "\n<thead>\n" . $content . "\n</thead>";
    }

    /**
     * Renders a table row with the given data arClass and key.
     *
     * @param ActiveRecord|array $arClass the data arClass to be rendered
     * @param mixed $key the key associated with the data arClass
     * @param int $index the zero-based index of the data arClass among the arClass array returned by
     * {@see dataProvider}.
     *
     * @return string the rendering result
     */
    private function renderTableRow($arClass, $key, int $index): string
    {
        $cells = [];

        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                $cells[] = $column->renderDataCell($arClass, $key, $index);
            }
        }

        if ($cells !== [] && $this->frameworkCss === static::BULMA) {
            $this->rowOptions['data-key'] = is_array($key) ? json_encode($key) : (string) $key;
        }

        return $this->html->tag('tr', implode('', $cells), $this->rowOptions);
    }

    private function renderToolbar(): string
    {
        $html = '';
        $toolbar = '';

        foreach ($this->toolbar as $item) {
            /** @var string */
            $content = $item['content'] ?? '';
            /** @var array */
            $options = $item['options'] ?? [];

            $toolbar .= $this->html->tag('div', $content . "\n", $options);
        }

        if ($toolbar !== '') {
            $html = $this->html->tag('div', $toolbar, $this->toolbarOptions);
        }

        return $html;
    }

    private function checkColumnButtonFunctions(array $column, array $buttons = null): ?array
    {
        if (isset($column['buttons'])) {
            /** @var array */
            $buttons = $column['buttons'];
        }

        return $buttons;
    }

    private function checkColumnContentFunctions(array $column, callable $content = null): ?callable
    {
        if (isset($column['content'])) {
            /** @var callable|null */
            $content = $column['content'];
        }

        return $content;
    }

    /**
     * @param Closure|string|null $value
     *
     * @return Closure|string|null
     */
    private function checkColumnValueFunctions(array $column, $value = null)
    {
        if (isset($column['value'])) {
            /** @var Closure|string|null */
            $value = $column['value'];
        }

        return $value;
    }

    private function checkColumnVisibleButtonsFunctions(array $column, array $visibleButtons = null): ?array
    {
        if (isset($column['visibleButtons'])) {
            /** @var array */
            $visibleButtons = $column['visibleButtons'];
        }

        return $visibleButtons;
    }
}
