<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use JsonException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Html\Html;
use Yiisoft\Json\Json;

/**
 * CheckboxColumn displays a column of checkboxes in a grid view.
 *
 * To add a CheckboxColumn to the {@see GridView}, add it to the {@see GridView::columns|columns} configuration as
 * follows:
 *
 * ```php
 * 'columns' => [
 *     // ...
 *     [
 *         'class' => 'yii\grid\CheckboxColumn',
 *         // you may configure additional properties here
 *     ],
 * ]
 * ```
 *
 * Users may click on the checkboxes to select rows of the grid. The selected rows may be
 * obtained by calling the following JavaScript code:
 *
 * ```javascript
 * var keys = $('#grid').yiiGridView('getSelectedRows');
 * // keys is an array consisting of the keys associated with the selected rows
 * ```
 *
 * For more details and usage information on CheckboxColumn.
 *
 * {@see the [guide article on data widgets](guide:output-data-widgets)}.
 */
class CheckboxColumn extends Column
{
    /**
     * @var string the name of the input checkbox input fields. This will be appended with `[]` to ensure it is an
     * array.
     */
    public string $name = 'selection';

    /**
     * @var array|\Closure the HTML attributes for checkboxes. This can either be an array of attributes or an anonymous
     * function ({@see Closure}) that returns such an array. The signature of the function should be the following:
     * `function ($arClass, $key, $index, $column)`.
     *
     * Where `$arClass`, `$key`, and `$index` refer to the arClass, key and index of the row currently being rendered
     * and `$column` is a reference to the {@see CheckboxColumn} object.
     *
     * A function may be used to assign different attributes to different rows based on the data in that row.
     * Specifically if you want to set a different value for the checkbox
     * you can use this option in the following way (in this example using the `name` attribute of the arClass):
     *
     * ```php
     * 'checkboxOptions' => function ($arClass, $key, $index, $column) {
     *     return ['value' => $arClass->name];
     * }
     * ```
     *
     * @see Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $checkboxOptions = [];

    /**
     * @var bool whether it is possible to select multiple rows. Defaults to `true`.
     */
    public bool $multiple = true;

    /**
     * @var string the css class that will be used to find the checkboxes.
     */
    public string $cssClass = '';

    public function __construct()
    {
        if (empty($this->name)) {
            throw new InvalidConfigException('The "name" property must be set.');
        }

        if (substr_compare($this->name, '[]', -2, 2)) {
            $this->name .= '[]';
        }

        $this->registerClientScript();
    }

    /**
     * Renders the header cell content.
     *
     * The default implementation simply renders {@see header}.
     * This method may be overridden to customize the rendering of the header cell.
     *
     * @throws JsonException
     *
     * @return string the rendering result
     */
    protected function renderHeaderCellContent(): string
    {
        if ($this->header !== null || !$this->multiple) {
            return parent::renderHeaderCellContent();
        }

        return Html::checkbox($this->getHeaderCheckBoxName(), false, ['class' => 'select-on-check-all']);
    }

    protected function renderDataCellContent($arClass, $key, int $index): ?string
    {
        if ($this->content !== null) {
            return parent::renderDataCellContent($arClass, $key, $index);
        }

        if ($this->checkboxOptions instanceof Closure) {
            $options = call_user_func($this->checkboxOptions, $arClass, $key, $index, $this);
        } else {
            $options = $this->checkboxOptions;
        }

        if (!isset($options['value'])) {
            $options['value'] = is_array($key) ? Json::encode($key) : $key;
        }

        if ($this->cssClass !== '') {
            Html::addCssClass($options, $this->cssClass);
        }

        return Html::checkbox($this->name, !empty($options['checked']), $options);
    }

    /**
     * Returns header checkbox name.
     *
     * @return string header checkbox name.
     */
    protected function getHeaderCheckBoxName(): string
    {
        $name = $this->name;

        if (substr_compare($name, '[]', -2, 2) === 0) {
            $name = substr($name, 0, -2);
        }
        if (substr_compare($name, ']', -1, 1) === 0) {
            $name = substr($name, 0, -1) . '_all]';
        } else {
            $name .= '_all';
        }

        return $name;
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript(): void
    {
    }
}
