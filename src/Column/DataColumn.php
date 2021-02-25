<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use Yii\Extension\GridView\Helper\Html;
use Yii\Extension\GridView\Widget\LinkSorter;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Strings\Inflector;

/**
 * DataColumn is the default column type for the {@see GridView} widget.
 *
 * It is used to show data columns and allows {@see enableSorting|sorting} and {@see filter|filtering} them.
 *
 * A simple data column definition refers to an attribute in the data arClass of the GridView's data provider.
 *
 * The name of the attribute is specified by {@see attribute}.
 *
 * By setting {@see value} and {@see label}, the header and cell content can be customized.
 *
 * A data column differentiates between the {@see getDataCellValue|data cell value} and the
 * {@see renderDataCellContent|data cell content}. The cell value is an un-formatted value that may be used for
 * calculation, while the actual cell content is a {@see format|formatted} version of that value which may contain HTML
 * markup.
 *
 * For more details and usage information on DataColumn, see the:
 * [guide article on data widgets](guide:output-data-widgets).
 */
final class DataColumn extends Column
{
    private string $attribute = '';
    private bool $encodeLabel = true;
    private bool $enableSorting = true;
    private string $filter = '';
    public string $filterAttribute = '';
    private array $filterInputOptions = ['id' => null];
    /** @var bool|float|int|string|null */
    private $filterValueDefault = null;
    private string $format = 'text';
    private Inflector $inflector;
    private array $sortLinkOptions = [];
    /** @var string|Closure|null */
    private $value;

    public function __construct(Inflector $inflector, Html $html, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($html, $urlGenerator);

        $this->inflector = $inflector;
        $this->html = $html;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string the attribute name associated with this column. When neither {@see content} nor {@see value} is
     * specified, the value of the specified attribute will be retrieved from each data arClass and displayed.
     *
     * Also, if {@see label} is not specified, the label associated with the attribute will be displayed.
     *
     * @return $this
     */
    public function attribute(string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @param string the HTML code representing a filter input (e.g. a text field, a dropdown list) that is used for
     * this data column. This property is effective only when {@see filterModel} is set.
     *
     * - If this property is not set, a text field will be generated as the filter input with attributes defined
     *   with {@see filterInputOptions}. See {@see Html::activeInput} for details on how an active input tag is
     *   generated.
     * - If this property is an array, a dropdown list will be generated that uses this property value as the list
     *   options.
     * - If you don't want a filter for this data column, set this value to be false.
     *
     * @return $this
     */
    public function filter(string $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @param string the attribute name of the {@see filterModel} associated with this column. If not set, will have the
     * same value as {@see attribute}.
     *
     * @return $this
     */
    public function filterAttribute(string $filterAttribute): self
    {
        $this->filterAttribute = $filterAttribute;

        return $this;
    }

    /**
     * @param array the HTML attributes for the filter input fields.
     *
     * This property is used in combination with the {@see filter} property. When {@see filter} is not set or is an
     * array, this property will be used to render the HTML attributes for the generated filter input fields.
     *
     * Empty `id` in the default value ensures that id would not be obtained from the arClass attribute thus
     * providing better performance.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function filterInputOptions(array $filterInputOptions)
    {
        $this->filterInputOptions = $filterInputOptions;

        return $this;
    }

    /**
     * Set filter value default text input field.
     *
     * @param bool|float|int|string|null $filterValueDefault
     *
     * @return $this
     */
    public function filterValueDefault($filterValueDefault): self
    {
        $this->filterValueDefault = $filterValueDefault;

        return $this;
    }

    /**
     * @param string in which format should the value of each data arClass be displayed as
     * (e.g. `"raw"`, `"text"`, `"html"`).
     *
     * Supported formats are determined by the {@see formatter} used by the {@see GridView}.
     *
     * Default format is "text" which will format the value as an HTML-encoded plain text when {@see formatter} is used
     * as the {@see formatter} of the GridView.
     *
     * @return $this
     *
     * {@see format()}
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @param bool whether the header label should not be HTML-encoded.
     *
     * @return $this
     *
     * {@see label}
     */
    public function notEncodeLabel(): self
    {
        $this->encodeLabel = false;

        return $this;
    }

    /**
     * @param bool whether to allow sorting by this column. If true and {@see attribute} is found in the sort definition
     * of {@see dataProvider}, then the header cell of this column will contain a link that may trigger the sorting when
     * being clicked.
     *
     * @return $this
     */
    public function notSorting(): self
    {
        $this->enableSorting = false;

        return $this;
    }

    /**
     * @param array the HTML attributes for the link tag in the header cell enerated by {@see Sort::link} when sorting
     * is enabled for this column.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function sortLinkOptions(array $sortLinkOptions): self
    {
        $this->sortLinkOptions = $sortLinkOptions;

        return $this;
    }

    /**
     * @param string|Closure|null $value an anonymous function or a string that is used to determine the value to
     * display in the current column.
     *
     * If this is an anonymous function, it will be called for each row and the return value will be used as the value
     * to display for every data arClass. The signature of this function should be:
     *
     * `function ($arClass, $key, $index, $column)`.
     *
     * Where `$arClass`, `$key`, and `$index` refer to the arClass, key and index of the row currently being rendered
     * and `$column` is a reference to the {@see DataColumn} object.
     *
     * You may also set this property to a string representing the attribute name to be displayed in this column.
     * This can be used when the attribute to be displayed is different from the {@see attribute} that is used for
     * sorting and filtering.
     *
     * If this is not set, `$arClass[$attribute]` will be used to obtain the value, where `$attribute` is the value of
     * {@see attribute}.
     *
     * @return $this
     */
    public function value($value): self
    {
        $this->value = $value;

        return $this;
    }

    protected function renderHeaderCellContent(): string
    {
        if ($this->label === '') {
            $this->label = parent::renderHeaderCellContent();
        }

        if ($this->encodeLabel) {
            $this->label = $this->html->encode($this->label);
        }

        $sort = $this->grid->getSort();

        if ($this->attribute !== '' && $this->enableSorting && $sort->hasAttribute($this->attribute)) {
            return LinkSorter::widget()
                ->attributes($this->attribute)
                ->frameworkCss($this->grid->getFrameworkCss())
                ->pagination($this->grid->getPagination())
                ->requestAttributes($this->grid->getRequestAttributes())
                ->requestQueryParams($this->grid->getRequestQueryParams())
                ->linkOptions(array_merge($this->sortLinkOptions, ['label' => $this->label]))
                ->sort($sort)
                ->render();
        }

        return $this->label;
    }

    protected function getHeaderCellLabel(): string
    {
        return $this->label !== '' ? $this->label : $this->inflector->toHumanReadable($this->attribute) ;
    }

    protected function renderFilterCellContent(): string
    {
        if ($this->filter !== '') {
            return $this->filter;
        }

        if ($this->filterAttribute !== '') {
            if ($this->grid->getFrameworkCss() === 'bulma') {
                $this->html->AddCssClass($this->filterInputOptions, ['input' => 'input']);
            } else {
                $this->html->AddCssClass($this->filterInputOptions, ['input' => 'form-control']);
            }

            $name = $this->html->getInputName($this->grid->getFilterModelName(), $this->filterAttribute);

            return $this->html->textInput($name, $this->filterValueDefault, $this->filterInputOptions);
        }

        return parent::renderFilterCellContent();
    }

    /**
     * Returns the data cell value.
     *
     * @param array|object $arClass the data arClass
     * @param mixed $key the key associated with the data arClass
     * @param int $index the zero-based index of the data arClass among the active record classes array returned by
     * {@see GridView::dataProvider}.
     *
     * @return string the data cell value
     */
    public function getDataCellValue($arClass, $key, int $index): string
    {
        if ($this->value !== null) {
            if (is_string($this->value)) {
                return (string) ArrayHelper::getValue($arClass, $this->value);
            }

            return (string) call_user_func($this->value, $arClass, $key, $index, $this);
        } elseif ($this->attribute !== '') {
            return (string) ArrayHelper::getValue($arClass, $this->attribute);
        }

        return '';
    }

    /**
     * Renders the data cell content.
     *
     * @param array|object $arClass the data arClass.
     * @param mixed $key the key associated with the data arClass.
     * @param int $index the zero-based index of the data arClass among the arClasss array returned by
     * {@see GridView::dataProvider}.
     *
     * @return string the rendering result.
     */
    protected function renderDataCellContent($arClass, $key, int $index): string
    {
        if (!empty($this->content)) {
            return parent::renderDataCellContent($arClass, $key, $index);
        }

        return $this->getDataCellValue($arClass, $key, $index);
    }
}
