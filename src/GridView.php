<?php

declare(strict_types=1);

namespace Yii\Extension\GridView;

use Closure;
use JsonException;
use Yii\Extension\GridView\Column\Column;
use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Widget\BaseListView;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Form\FormModel;
use Yiisoft\Html\Html;
use Yiisoft\Json\Json;
use Yiisoft\Router\FastRoute\UrlGenerator;

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
    protected array $options = ['class' => 'grid-view'];
    private FormModel $filterModel;
    private ?Closure $afterRow = null;
    private ?Closure $beforeRow = null;
    private array $captionOptions = [];
    private array $columns = [];
    private array $filterErrorOptions = ['class' => 'help-block'];
    private array $filterErrorSummaryOptions = ['class' => 'error-summary'];
    private array $filterRowOptions = ['class' => 'filters'];
    private array $footerRowOptions = [];
    private array $headerRowOptions = [];
    private array $rowOptions = [];
    private array $tableOptions = ['class' => 'table'];
    private bool $filterOnFocusOut = true;
    private bool $placeFooterAfterBody = false;
    private bool $showFooter = false;
    private bool $showHeader = true;
    private int $currentPage = 0;
    private int $pageSize = 0;
    private string $caption = '';
    private string $dataColumnClass = DataColumn::class;
    private string $emptyCell = '&nbsp;';
    private string $filterPosition = self::FILTER_POS_BODY;

    protected function run(): string
    {
        if (!isset($this->dataProvider)) {
            throw new InvalidConfigException('The "dataProvider" property must be set.');
        }

        $pagination = $this->getPagination();
        $pagination->currentPage($this->currentPage);

        if ($this->pageSize > 0) {
            $pagination->pageSize($this->pageSize);
        }

        if ($this->emptyText !== '') {
            $this->emptyText = $this->translator->translate($this->emptyText, [], 'yii-gridview');
        }

        if (!isset($this->options['id'])) {
            $this->options['id'] = "{$this->getId()}-gridview";
        }

        $options = Json::htmlEncode(
            array_merge($this->getClientOptions(), ['filterOnFocusOut' => $this->filterOnFocusOut])
        );

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
     * @param string $caption the caption of the grid table.
     *
     * @return $this
     *
     * {@see captionOptions}
     */
    public function caption(string $caption)
    {
        $new = clone $this;
        $new->caption = $caption;

        return $new;
    }

    /**
     * @param array $captionOptions the HTML attributes for the caption element.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     * {@see caption}
     */
    public function captionOptions(array $captionOptions): self
    {
        $new = clone $this;
        $new->captionOptions = $captionOptions;

        return $new;
    }

    /**
     * @param array $columns grid column configuration. Each array element represents the configuration for one
     * particular grid column. For example,
     *
     * ```php
     * [
     *     [
     *         '__class' => SerialColumn::class,
     *     ],
     *     [
     *         '__class' => DataColumn::class, // this line is optional
     *         'attribute()' => ['name'],
     *         'format()' => ['text'],
     *         'label()' => ['Name'],
     *     ],
     *     [
     *         '__class' => CheckboxColumn::class,
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
     */
    public function columns(array $value): self
    {
        $new = clone $this;
        $new->columns = $value;

        return $new;
    }

    public function currentPage(int $currentPage): self
    {
        $new = clone $this;
        $new->currentPage = $currentPage;

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
     * @param array the options for rendering every filter error message.
     *
     * This is mainly used by {@see Html::error()} when rendering an error message next to every filter input field.
     */
    public function filterErrorOptions(array $filterErrorOptions): self
    {
        $new = clone $this;
        $new->filterErrorOptions = $filterErrorOptions;

        return $new;
    }

    /**
     * @param array $filterErrorSummaryOptions the options for rendering the filter error summary.
     *
     * Please refer to {@see Html::errorSummary()} for more details about how to specify the options.
     *
     * {@see renderErrors()}
     */
    public function filterErrorSummaryOptions(array $filterErrorSummaryOptions): self
    {
        $new = clone $this;
        $new->filterErrorSummaryOptions = $filterErrorSummaryOptions;

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
     * @param FormModel|null the arClass that keeps the user-entered filter data. When this property is set, the grid
     * view will enable column-based filtering. Each data column by default will display a text field at the top that
     * users can fill in to filter the data.
     *
     * Note that in order to show an input field for filtering, a column must have its {@see DataColumn::attribute}
     * property set and the attribute should be active in the current scenario of $filterModel or have
     * {@see DataColumn::filter} set as the HTML code for the input field.
     *
     * When this property is not set (null) the filtering feature is disabled.
     */
    public function filterModel(FormModel $filterModel): self
    {
        $new = clone $this;
        $new->filterModel = $filterModel;

        return $new;
    }

    /**
     * @param bool $filterOnFocusOut whatever to apply filters on losing focus. Leaves an ability to manage filters via
     * yiiGridView JS.
     *
     * @return $this;
     */
    public function filterOnFocusOut(bool $filterOnFocusOut): self
    {
        $new = clone $this;
        $new->filterOnFocusOut = $filterOnFocusOut;

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

    public function getFilterErrorOptions(): array
    {
        return $this->filterErrorOptions;
    }

    public function getfilterModel(): FormModel
    {
        return $this->filterModel;
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

    public function pageSize(int $pageSize): self
    {
        $new = clone $this;
        $new->pageSize = $pageSize;

        return $new;
    }

    /**
     * Whether to place footer after body in DOM if $showFooter is true.
     */
    public function placeFooterAfterBody(): self
    {
        $new = clone $this;
        $this->placeFooterAfterBody = true;

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

    protected function renderSection(string $name): string
    {
        if ($name == '{errors}') {
            return $this->renderErrors();
        } else {
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
        $caption = $this->renderCaption();
        $columnGroup = $this->renderColumnGroup();
        $tableHeader = $this->showHeader ? $this->renderTableHeader() : false;
        $tableBody = $this->renderTableBody();

        $tableFooter = false;
        $tableFooterAfterBody = false;

        if ($this->showFooter) {
            if ($this->placeFooterAfterBody) {
                $tableFooterAfterBody = $this->renderTableFooter();
            } else {
                $tableFooter = $this->renderTableFooter();
            }
        }

        $content = array_filter([
            $caption,
            $columnGroup,
            $tableHeader,
            $tableFooter,
            $tableBody,
            $tableFooterAfterBody,
        ]);

        return Html::tag('table', implode("\n", $content), array_merge($this->tableOptions, ['encode' => false]));
    }

    /**
     * Creates a {@see DataColumn} object based on a string in the format of "attribute:format:label".
     *
     * @param string $text the column specification string
     *
     * @throws InvalidConfigException if the column specification is invalid
     *
     * @return DataColumn the column instance
     */
    private function createDataColumn($text): DataColumn
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        $dataColumn = new DataColumn();
        $dataColumn->grid = $this;
        $dataColumn->attribute = $matches[1];
        $dataColumn->format = isset($matches[3]) ? $matches[3] : 'text';
        $dataColumn->label = isset($matches[5]) ? $matches[5] : '';

        return $dataColumn;
    }

    /**
     * Returns the options for the grid view JS widget.
     *
     * @return array the options
     */
    private function getClientOptions(): array
    {
        return [];
    }

    /**
     * This function tries to guess the columns to show from the given data if {@see columns} are not explicitly
     * specified.
     */
    private function guessColumns(): void
    {
        $arClasses = $this->dataProvider->getARClasses();
        $arClass = reset($arClasses);

        if (is_array($arClass) || is_object($arClass)) {
            foreach ($arClass as $name => $value) {
                if ($value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                    $this->columns[] = (string) $name;
                }
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
            } else {
                $buttons = null;
                $value = null;
                $visibleButtons = null;

                if (isset($column['buttons()'])) {
                    $buttons = $column['buttons()'];
                    unset($column['buttons()']);
                }

                if (isset($column['value'])) {
                    $value = $column['value'];
                    unset($column['value']);
                }

                if (isset($column['visibleButtons()'])) {
                    $buttons = $column['visibleButtons()'];
                    unset($column['visibleButtons()']);
                }

                $config = array_merge(
                    [
                        '__class' => $this->dataColumnClass,
                        'grid' => $this,
                    ],
                    $column,
                );

                $column = $this->gridViewFactory->createColumnClass($config);

                if ($buttons !== null) {
                    $column->buttons($buttons);
                }

                if ($value !== null) {
                    $column->value = $value;
                }

                if ($visibleButtons !== null) {
                    $column->visibleButtons($value);
                }
            }

            if (!$column->visible) {
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
    private function renderCaption(): string
    {
        if (!empty($this->caption)) {
            return Html::tag('caption', $this->caption, array_merge($this->captionOptions, ['encode' => false]));
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
            /* @var $column Column */
            if (!empty($column->options)) {
                $cols = [];
                foreach ($this->columns as $col) {
                    $cols[] = Html::tag('col', '', $col->options);
                }

                return Html::tag('colgroup', implode("\n", $cols));
            }
        }

        return '';
    }

    /**
     * Renders validator errors of filter arClass.
     *
     * @return string the rendering result.
     */
    private function renderErrors(): string
    {
        if ($this->filterModel instanceof ActiveRecord && $this->filterModel->hasErrors()) {
            return Html::errorSummary($this->filterModel, $this->filterErrorSummaryOptions);
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
        if ($this->filterModel !== null) {
            $cells = [];

            foreach ($this->columns as $column) {
                /* @var $column Column */
                $cells[] = $column->renderFilterCell();
            }

            return Html::tag('tr', implode('', $cells), array_merge($this->filterRowOptions, ['encode' => false]));
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
        foreach ($arClasses as $index => $arClass) {
            $key = $keys[$index];
            if ($this->beforeRow !== null) {
                $row = call_user_func($this->beforeRow, $arClass, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }

            $rows[] = $this->renderTableRow($arClass, $key, $index);

            if ($this->afterRow !== null) {
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
            /* @var $column Column */
            $cells[] = $column->renderFooterCell();
        }

        $content = Html::tag('tr', implode('', $cells), array_merge($this->footerRowOptions, ['encode' => false]));

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
            /* @var $column Column */
            $cells[] = $column->renderHeaderCell();
        }

        $content = Html::tag('tr', implode('', $cells), array_merge($this->headerRowOptions, ['encode' => false]));

        if ($this->filterPosition === self::FILTER_POS_HEADER) {
            $content = $this->renderFilters() . $content;
        } elseif ($this->filterPosition === self::FILTER_POS_BODY) {
            $content .= $this->renderFilters();
        }

        return "<thead>\n" . $content . "\n</thead>";
    }

    /**
     * Renders a table row with the given data arClass and key.
     *
     * @param ActiveRecord|array $arClass the data arClass to be rendered
     * @param mixed $key the key associated with the data arClass
     * @param int $index the zero-based index of the data arClass among the arClass array returned by {@see dataProvider}.
     *
     * @return string the rendering result
     */
    private function renderTableRow($arClass, $key, int $index): string
    {
        $cells = [];

        /* @var $column Column */
        foreach ($this->columns as $column) {
            $cells[] = $column->renderDataCell($arClass, $key, $index);
        }

        if ($this->rowOptions instanceof Closure) {
            $options = call_user_func($this->rowOptions, $arClass, $key, $index, $this);
        } else {
            $options = $this->rowOptions;
        }

        $options['data-key'] = is_array($key) ? json_encode($key) : (string) $key;

        return Html::tag('tr', implode('', $cells), array_merge($options, ['encode' => false]));
    }
}
