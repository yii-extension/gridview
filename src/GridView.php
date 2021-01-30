<?php

declare(strict_types=1);

namespace Yii\Extension\GridView;

use Closure;
use JsonException;
use Yii\Extension\GridView\Column\Column;
use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\ExceptionInvalidConfigException;
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

    /**
     * @var string the default data column class if the class name is not explicitly specified when configuring a data
     * column.
     */
    public string $dataColumnClass = DataColumn::class;

    /**
     * @var string the caption of the grid table.
     *
     * {@see captionOptions}
     */
    public string $caption;

    /**
     * @var array the HTML attributes for the caption element.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     * {@see caption}
     */
    public array $captionOptions = [];

    /**
     * @var array the HTML attributes for the grid table element.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $tableOptions = ['class' => 'table table-striped table-bordered'];

    /**
     * @var array the HTML attributes for the container tag of the grid view.
     * The "tag" element specifies the tag name of the container element and defaults to "div".
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $options = ['class' => 'grid-view'];

    /**
     * @var array the HTML attributes for the table header row.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $headerRowOptions = [];

    /**
     * @var array the HTML attributes for the table footer row.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $footerRowOptions = [];

    /**
     * @var array|Closure the HTML attributes for the table body rows. This can be either an array specifying the common
     * HTML attributes for all body rows, or an anonymous function that returns an array of the HTML attributes. The
     * anonymous function will be called once for every data arClass returned by {@see dataProvider}. It should have the
     * following signature:
     *
     * ```php
     * function ($arClass, $key, $index, $grid)
     * ```
     *
     * - `$arClass`: the current data arClass being rendered.
     * - `$key`: the key value associated with the current data arClass.
     * - `$index`: the zero-based index of the data arClass in the arClass array returned by {@see dataProvider}.
     * - `$grid`: the GridView object.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public $rowOptions = [];

    /**
     * @var Closure|null an anonymous function that is called once BEFORE rendering each data arClass. It should have the
     * similar signature as {@see rowOptions}. The return result of the function will be rendered directly.
     */
    public ?Closure $beforeRow = null;

    /**
     * @var Closure|null an anonymous function that is called once AFTER rendering each data arClass. It should have the
     * similar signature as {@see rowOptions}. The return result of the function will be rendered directly.
     */
    public ?Closure $afterRow = null;

    /**
     * @var bool whether to show the header section of the grid table.
     */
    public bool $showHeader = true;

    /**
     * @var bool whether to show the footer section of the grid table.
     */
    public bool $showFooter = true;

    /**
     * @var bool whether to place footer after body in DOM if $showFooter is true.
     */
    public bool $placeFooterAfterBody = false;

    /**
     * @var bool whether to show the grid view if [[dataProvider]] returns no data.
     */
    public bool $showOnEmpty = true;

    /**
     * @var array|Formatter the formatter used to format arClass attribute values into displayable texts. This can be
     * either an instance of {@see Formatter} or an configuration array for creating the {@see Formatter} instance. If
     * this property is not set, the "formatter" application component will be used.
     */
    public $formatter;

    /**
     * @var array grid column configuration. Each array element represents the configuration for one particular grid
     * column. For example,
     *
     * ```php
     * [
     *     ['class' => SerialColumn::className()],
     *     [
     *         'class' => DataColumn::className(), // this line is optional
     *         'attribute' => 'name',
     *         'format' => 'text',
     *         'label' => 'Name',
     *     ],
     *     ['class' => CheckboxColumn::className()],
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
     *     'attribute' => 'author.name',
     *     // ...
     * ]
     * ```
     */
    public array $columns = [];

    /**
     * @var string the HTML display when the content of a cell is empty. This property is used to render cells that have
     * no defined content, e.g. empty footer or filter cells.
     *
     * Note that this is not used by the {@see DataColumn} if a data item is `null`. In that case the
     * [[\yii\i18n\Formatter::nullDisplay|nullDisplay]] property of the {@see formatter} will be used to indicate an
     * empty data value.
     */
    public string $emptyCell = '&nbsp;';

    /**
     * @var Model the arClass that keeps the user-entered filter data. When this property is set, the grid view will
     * enable column-based filtering. Each data column by default will display a text field at the top that users can
     * fill in to filter the data.
     *
     * Note that in order to show an input field for filtering, a column must have its {@see DataColumn::attribute}
     * property set and the attribute should be active in the current scenario of $filterModel or have
     * {@see DataColumn::filter} set as the HTML code for the input field.
     *
     * When this property is not set (null) the filtering feature is disabled.
     */
    public $filterModel;

    /**
     * @var string|array the URL for returning the filtering result. {@see UrlGenerator::generate()]] will be called to
     * normalize the URL. If not set, the current controller action will be used.
     *
     * When the user makes change to any filter input, the current filtering inputs will be appended as GET parameters
     * to this URL.
     */
    public $filterUrl;

    /**
     * @var string additional jQuery selector for selecting filter input fields
     */
    public string $filterSelector;

    /**
     * @var string whether the filters should be displayed in the grid view. Valid values include:
     *
     * - {@see FILTER_POS_HEADER}: the filters will be displayed on top of each column's header cell.
     * - {@see FILTER_POS_BODY}: the filters will be displayed right below each column's header cell.
     * - {@see FILTER_POS_FOOTER}: the filters will be displayed below each column's footer cell.
     */
    public string $filterPosition = self::FILTER_POS_BODY;

    /**
     * @var array the HTML attributes for the filter row element.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $filterRowOptions = ['class' => 'filters'];

    /**
     * @var array the options for rendering the filter error summary.
     *
     * Please refer to [[Html::errorSummary()]] for more details about how to specify the options.
     *
     * {@see renderErrors()}
     */
    public array $filterErrorSummaryOptions = ['class' => 'error-summary'];

    /**
     * @var array the options for rendering every filter error message.
     *
     * This is mainly used by [[Html::error()]] when rendering an error message next to every filter input field.
     */
    public array $filterErrorOptions = ['class' => 'help-block'];

    /**
     * @var bool whatever to apply filters on losing focus. Leaves an ability to manage filters via yiiGridView JS.
     */
    public bool $filterOnFocusOut = true;

    /**
     * @var string the layout that determines how different sections of the grid view should be organized.
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{errors}`: the filter arClass error summary. See [[renderErrors()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
     */
    public string $layout = "{items}\n{summary}\n{pager}";

    public function run(): string
    {
        if ($this->dataProvider === null) {
            throw new InvalidConfigException('The "dataProvider" property must be set.');
        }

        if ($this->emptyText === null) {
            $this->emptyText = $this->translator->translate('No results found.', [], 'yii-gridview');
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
     * Renders validator errors of filter arClass.
     *
     * @return string the rendering result.
     */
    public function renderErrors(): string
    {
        if ($this->filterModel instanceof Model && $this->filterModel->hasErrors()) {
            return Html::errorSummary($this->filterModel, $this->filterErrorSummaryOptions);
        }

        return '';
    }

    public function renderSection(string $name)
    {
        if ($name == '{errors}') {
            return $this->renderErrors();
        } else {
            return parent::renderSection($name);
        }
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
     * Renders the data active record classes for the grid view.
     *
     * @throws JsonException
     *
     * @return string the HTML code of table
     */
    public function renderItems(): string
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

        return Html::tag('table', implode("\n", $content), $this->tableOptions);
    }

    /**
     * Renders the caption element.
     *
     * @throws JsonException
     *
     * @return bool|string the rendered caption element or `false` if no caption element should be rendered.
     */
    public function renderCaption()
    {
        if (!empty($this->caption)) {
            return Html::tag('caption', $this->caption, $this->captionOptions);
        }

        return false;
    }

    /**
     * Renders the column group HTML.
     *
     * @throws JsonException
     *
     * @return bool|string the column group HTML or `false` if no column group should be rendered.
     */
    public function renderColumnGroup()
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

        return false;
    }

    /**
     * Renders the table header.
     *
     * @return string the rendering result.
     */
    public function renderTableHeader()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            /* @var $column Column */
            $cells[] = $column->renderHeaderCell();
        }
        $content = Html::tag('tr', implode('', $cells), $this->headerRowOptions);
        if ($this->filterPosition === self::FILTER_POS_HEADER) {
            $content = $this->renderFilters() . $content;
        } elseif ($this->filterPosition === self::FILTER_POS_BODY) {
            $content .= $this->renderFilters();
        }

        return "<thead>\n" . $content . "\n</thead>";
    }

    /**
     * Renders the table footer.
     *
     * @return string the rendering result.
     */
    public function renderTableFooter()
    {
        $cells = [];

        foreach ($this->columns as $column) {
            /* @var $column Column */
            $cells[] = $column->renderFooterCell();
        }

        $content = Html::tag('tr', implode('', $cells), $this->footerRowOptions);

        if ($this->filterPosition === self::FILTER_POS_FOOTER) {
            $content .= $this->renderFilters();
        }

        return "<tfoot>\n" . $content . "\n</tfoot>";
    }

    /**
     * Renders the filter.
     *
     * @return string the rendering result.
     */
    public function renderFilters()
    {
        if ($this->filterModel !== null) {
            $cells = [];

            foreach ($this->columns as $column) {
                /* @var $column Column */
                $cells[] = $column->renderFilterCell();
            }

            return Html::tag('tr', implode('', $cells), $this->filterRowOptions);
        }

        return '';
    }

    /**
     * Renders the table body.
     *
     * @return string the rendering result.
     */
    public function renderTableBody(): string
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

        if (empty($rows) && $this->emptyText !== false) {
            $colspan = count($this->columns);

            return "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        }

        return "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
    }

    /**
     * Renders a table row with the given data arClass and key.
     *
     * @param mixed $arClass the data arClass to be rendered
     * @param mixed $key the key associated with the data arClass
     * @param int $index the zero-based index of the data arClass among the arClass array returned by {@see dataProvider}.
     *
     * @return string the rendering result
     */
    public function renderTableRow($arClass, $key, $index)
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

        return Html::tag('tr', implode('', $cells), $options);
    }

    public function withColumns(array $value): self
    {
        $new = clone $this;
        $new->columns = $value;

        return $new;
    }

    public function withDataProvider($value): self
    {
        $new = clone $this;
        $new->dataProvider = $value;

        return $new;
    }

    public function withPageActive(int $value): self
    {
        $new = clone $this;
        $new->dataProvider->getPagination()->page = $value;

        return $new;
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

                if (isset($column['buttons'])) {
                    $buttons = $column['buttons'];
                    unset($column['buttons']);
                }

                if (isset($column['value'])) {
                    $value = $column['value'];
                    unset($column['value']);
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
                    $column->buttons = $buttons;
                }

                if ($value !== null) {
                    $column->value = $value;
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
        $dataColumn->label = isset($matches[5]) ? $matches[5] : null;

        return $dataColumn;
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
}
