<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use Yii\Extension\GridView\Widget\LinkSorter;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Html\Html;
use Yiisoft\Form\FormModel;
use Yiisoft\Strings\Inflector;
use Yiisoft\Form\Widget\TextInput;

/**
 * DataColumn is the default column type for the {@see GridView} widget.
 *
 * It is used to show data columns and allows {@see enableSorting|sorting} and {@see filter|filtering} them.
 *
 * A simple data column definition refers to an attribute in the data arClass of the GridView's data provider. The name of
 * the attribute is specified by {@seee attribute}.
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
class DataColumn extends Column
{
    /**
     * @var string the attribute name associated with this column. When neither {@see content} nor {@see value} is
     * specified, the value of the specified attribute will be retrieved from each data arClass and displayed.
     *
     * Also, if {@see label} is not specified, the label associated with the attribute will be displayed.
     */
    public string $attribute = '';

    /**
     * @var bool whether the header label should be HTML-encoded.
     *
     * {@see label}
     */
    public bool $encodeLabel = true;

    /**
     * @var string|Closure an anonymous function or a string that is used to determine the value to display in the
     * current column.
     *
     * If this is an anonymous function, it will be called for each row and the return value will be used as the value
     * to display for every data arClass. The signature of this function should be:
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
     */
    public $value;

    /**
     * @var string|array|Closure in which format should the value of each data arClass be displayed as
     * (e.g. `"raw"`, `"text"`, `"html"`, `['date', 'php:Y-m-d']`). Supported formats are determined by the
     * {@see GridView::formatter|formatter} used by the {@see GridView}. Default format is "text" which will format the
     * value as an HTML-encoded plain text when [[\yii\i18n\Formatter]] is used as the
     * {@see GridView::$formatter|formatter} of the GridView.
     *
     * {@see \yii\i18n\Formatter::format()}
     */
    public $format = 'text';

    /**
     * @var bool whether to allow sorting by this column. If true and {@see attribute} is found in the sort definition
     * of {@see GridView::dataProvider}, then the header cell of this column will contain a link that may trigger the
     * sorting when being clicked.
     */
    public bool $enableSorting = true;

    /**
     * @var array the HTML attributes for the link tag in the header cell enerated by [[\yii\data\Sort::link]] when
     * sorting is enabled for this column.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $sortLinkOptions = [];

    /**
     * @var string|array|null|false the HTML code representing a filter input (e.g. a text field, a dropdown list)
     * that is used for this data column. This property is effective only when {@see GridView::filterModel} is set.
     *
     * - If this property is not set, a text field will be generated as the filter input with attributes defined
     * with {@see filterInputOptions}. See {@see Html::activeInput} for details on how an active input tag is
     * generated.
     * - If this property is an array, a dropdown list will be generated that uses this property value as the list
     * options.
     * - If you don't want a filter for this data column, set this value to be false.
     */
    public $filter;

    /**
     * @var array the HTML attributes for the filter input fields. This property is used in combination with
     * the [[filter]] property. When [[filter]] is not set or is an array, this property will be used to
     * render the HTML attributes for the generated filter input fields.
     *
     * Empty `id` in the default value ensures that id would not be obtained from the arClass attribute thus
     * providing better performance.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $filterInputOptions = ['id' => null];

    /**
     * @var string|null the attribute name of the {@see GridView::filterModel} associated with this column. If not set,
     * will have the same value as {@see attribute}.
     */
    public string $filterAttribute = '';

    protected function renderHeaderCellContent(): string
    {
        if ($this->label === '' && $this->attribute === '') {
            return parent::renderHeaderCellContent();
        }

        $label = $this->label;

        if ($this->encodeLabel) {
            $label = Html::encode($label);
        }

        $sort = $this->grid->getSort();

        if ($this->attribute !== '' && $this->enableSorting && $sort->hasAttribute($this->attribute)) {
            return LinkSorter::widget()
                ->attributes($this->attribute)
                ->frameworkCss($this->grid->getFrameworkCss())
                ->pagination($this->grid->getPagination())
                ->requestAttributes($this->grid->getRequestAttributes())
                ->requestQueryParams($this->grid->getRequestQueryParams())
                ->linkOptions(array_merge($this->sortLinkOptions, ['label' => $label]))
                ->sort($sort)
                ->render();
        }

        return $label;
    }

    protected function getHeaderCellLabel(): string
    {
        return $this->label !== '' ? $this->label : (new Inflector())->toPascalCase($this->attribute) ;
    }

    protected function renderFilterCellContent(): string
    {
        if ($this->filterAttribute === '') {
            $this->filterAttribute = $this->attribute;
        }

        if (is_string($this->filter)) {
            return $this->filter;
        }

        $arClass = $this->grid->getFilterModel();

        if (
            $this->filter !== false &&
            $arClass instanceof FormModel &&
            $this->filterAttribute !== '' &&
            $arClass->isAttributeActive($this->filterAttribute)
        ) {
            if ($arClass->hasErrors($this->filterAttribute)) {
                Html::addCssClass($this->filterOptions, 'has-error');
                $error = ' ' . Html::error($arClass, $this->filterAttribute, $this->grid->getFilterErrorOptions());
            } else {
                $error = '';
            }

            if (is_array($this->filter)) {
                $options = array_merge(['prompt' => ''], $this->filterInputOptions);
                return Html::activeDropDownList($arClass, $this->filterAttribute, $this->filter, $options) . $error;
            } elseif ($this->format === 'boolean') {
                $options = array_merge(['prompt' => ''], $this->filterInputOptions);
                return Html::activeDropDownList($arClass, $this->filterAttribute, [
                    1 => $this->grid->formatter->booleanFormat[1],
                    0 => $this->grid->formatter->booleanFormat[0],
                ], $options) . $error;
            }

            $options = array_merge(['maxlength' => true], $this->filterInputOptions);

            if ($this->grid->getFrameworkCss() === 'bulma') {
                Html::AddCssClass($options, ['input' => 'input']);
            } else {
                Html::AddCssClass($options, ['input' => 'form-control']);
            }

            return TextInput::widget()->config($arClass, $this->filterAttribute, $options)->render();
        }

        return parent::renderFilterCellContent();
    }

    /**
     * Returns the data cell value.
     * @param mixed $arClass the data arClass
     * @param mixed $key the key associated with the data arClass
     * @param int $index the zero-based index of the data arClass among the active record classes array returned by
     * {@see GridView::dataProvider}.
     *
     * @return string the data cell value
     */
    public function getDataCellValue($arClass, $key, int $index): ?string
    {
        if ($this->value !== null) {
            if (is_string($this->value)) {
                return ArrayHelper::getValue($arClass, $this->value);
            }

            return call_user_func($this->value, $arClass, $key, $index, $this);
        } elseif ($this->attribute !== null) {
            return (string) ArrayHelper::getValue($arClass, $this->attribute);
        }

        return null;
    }

    protected function renderDataCellContent($arClass, $key, int $index): ?string
    {
        if ($this->content === null) {
            return $this->getDataCellValue($arClass, $key, $index);
        }

        return parent::renderDataCellContent($arClass, $key, $index);
    }
}
