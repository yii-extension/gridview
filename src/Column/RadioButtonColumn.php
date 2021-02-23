<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Html;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * RadioButtonColumn displays a column of radio buttons in a grid view.
 *
 * To add a RadioButtonColumn to the {@see GridView}, add it to the {@see GridView::columns|columns} configuration as
 * follows:
 *
 * ```php
 * 'columns' => [
 *     // ...
 *     [
 *         'class' => 'yii\grid\RadioButtonColumn',
 *         'radioOptions' => function ($arClass) {
 *              return [
 *                  'value' => $arClass['value'],
 *                  'checked' => $arClass['value'] == 2
 *              ];
 *          }
 *     ],
 * ]
 * ```
 */
class RadioButtonColumn extends Column
{
    /**
     * @var string the name of the input radio button input fields.
     */
    public string $name = 'radioButtonSelection';

    /**
     * @var array|Closure the HTML attributes for the radio buttons. This can either be an array of attributes or an
     * anonymous function ({@see Closure}) returning such an array.
     *
     * The signature of the function should be as follows: `function ($arClass, $key, $index, $column)`  where
     * `$arClass`, `$key`, and `$index` refer to the arClass, key and index of the row currently being rendered and
     * `$column` is a reference to the {@see RadioButtonColumn} object.
     *
     * A function may be used to assign different attributes to different rows based on the data in that row.
     * Specifically if you want to set a different value for the radio button you can use this option
     * in the following way (in this example using the `name` attribute of the arClass):
     *
     * ```php
     * 'radioOptions' => function ($arClass, $key, $index, $column) {
     *     return ['value' => $arClass->attribute];
     * }
     * ```
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public $radioOptions = [];


    /**
     * @throws InvalidConfigException if {@see name} is not set.
     */
    public function __construct(Html $html, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($html, $urlGenerator);

        if (empty($this->name)) {
            throw new InvalidConfigException('The "name" property must be set.');
        }

        $this->html = $html;
        $this->urlGenerator = $urlGenerator;
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
    protected function renderDataCellContent($arClass, $key, $index): string
    {
        if ($this->content !== null) {
            return parent::renderDataCellContent($arClass, $key, $index);
        }

        if ($this->radioOptions instanceof Closure) {
            $options = call_user_func($this->radioOptions, $arClass, $key, $index, $this);
        } else {
            $options = $this->radioOptions;
            if (!isset($options['value'])) {
                $options['value'] = is_array($key)
                    ? json_encode($key, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : $key;
            }
        }

        $checked = $options['checked'] ?? false;

        return $this->html->radio($this->name, $checked, $options);
    }
}
