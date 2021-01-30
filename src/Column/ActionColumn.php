<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use Yiisoft\ActiveRecord\ActiveRecordInterface;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;

use function is_array;

/**
 * ActionColumn is a column for the {@see GridView} widget that displays buttons for viewing and manipulating the items.
 *
 * To add an ActionColumn to the gridview, add it to the {@see GridView::columns|columns} configuration as follows:
 *
 * ```php
 * 'columns' => [
 *     // ...
 *     [
 *         '__class' => ActionColumn::className(),
 *         // you may configure additional properties here
 *     ],
 * ]
 * ```
 *
 * For more details and usage information on ActionColumn.
 *
 * {@see the [guide article on data widgets](guide:output-data-widgets)}
 */
final class ActionColumn extends Column
{
    public array $headerOptions = ['class' => 'action-column'];

    /**
     * @var string the ID of the controller that should handle the actions specified here. If not set, it will use the
     * currently active controller. This property is mainly used by {@see urlGenerator} to create URLs for different
     * actions. The value of this property will be prefixed to each action name to form the route of the action.
     */
    public string $controller;

    /**
     * @var string the template used for composing each cell in the action column. Tokens enclosed within curly brackets
     * are treated as controller action IDs (also called *button names* in the context of action column). They will be
     * replaced by the corresponding button rendering callbacks specified in {@see buttons}. For example, the token
     * `{view}` will be replaced by the result of the callback `buttons['view']`. If a callback cannot be found, the
     * token will be replaced with an empty string.
     *
     * As an example, to only have the view, and update button you can add the ActionColumn to your GridView columns as
     * follows:
     *
     * ```php
     *     ['__class' => ActionColumn::class, 'template' => '{view} {update} {delete}'],
     * ```
     *
     * {@see buttons}
     */
    public string $template = '{view} {update} {delete}';

    /**
     * @var array button rendering callbacks. The array keys are the button names (without curly brackets), and the
     * values are the corresponding button rendering callbacks. The callbacks should use the following signature:
     *
     * ```php
     * function ($url, $arClass, $key) {
     *     // return the button HTML code
     * }
     * ```
     *
     * where `$url` is the URL that the column creates for the button, `$arClass` is the arClass object being rendered
     * for the current row, and `$key` is the key of the arClass in the data provider array.
     *
     * You can add further conditions to the button, for example only display it, when the arClass is editable (here
     * assuming you have a status field that indicates that):
     *
     * ```php
     * [
     *     'update' => function ($url, $arClass, $key) {
     *         return $arClass->status === 'editable' ? Html::a('Update', $url) : '';
     *     },
     * ],
     * ```
     */
    public array $buttons = [];

    /**
     * @var array visibility conditions for each button. The array keys are the button names (without curly brackets),
     * and the values are the boolean true/false or the anonymous function. When the button name is not specified in
     * this array it will be shown by default.
     *
     * The callbacks must use the following signature:
     *
     * ```php
     * function ($arClass, $key, $index) {
     *     return $arClass->status === 'editable';
     * }
     * ```
     *
     * Or you can pass a boolean value:
     *
     * ```php
     * [
     *     'update' => true,
     * ],
     * ```
     */
    public array $visibleButtons = [];

    /**
     * @var callable a callback that creates a button URL using the specified arClass information. The signature of the
     * callback should be the same as that of {@see createUrl()} it can accept additional parameter, which refers to
     * the column instance itself:
     *
     * ```php
     * function (string $action, mixed $arClass, mixed $key, integer $index, ActionColumn $this) {
     *     //return string;
     * }
     * ```
     *
     * If this property is not set, button URLs will be created using {@see createUrl()}.
     */
    public $urlCreator;

    /**
     * @var array html options to be applied to the {@see initDefaultButton()|default button}.
     */
    public array $buttonOptions = [];

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;

        $this->initDefaultButtons();
    }

    /**
     * Initializes the default button rendering callbacks.
     */
    private function initDefaultButtons(): void
    {
        $this->initDefaultButton('view', 'eye-open');
        $this->initDefaultButton('update', 'pencil');
        $this->initDefaultButton('delete', 'trash', [
            'data-confirm' => 'Are you sure you want to delete this item?',
            'data-method' => 'post',
        ]);
    }

    /**
     * Initializes the default button rendering callback for single button.
     *
     * @param string $name Button name as it's written in template
     * @param string $iconName The part of Bootstrap glyphicon class that makes it unique
     * @param array $additionalOptions Array of additional options
     */
    private function initDefaultButton(string $name, string $iconName, array $additionalOptions = [])
    {
        if (!isset($this->buttons[$name]) && strpos($this->template, '{' . $name . '}') !== false) {
            $this->buttons[$name] = function ($url) use ($name, $iconName, $additionalOptions) {
                switch ($name) {
                    case 'view':
                        $title = 'View';
                        break;
                    case 'update':
                        $title = 'Update';
                        break;
                    case 'delete':
                        $title = 'Delete';
                        break;
                    default:
                        $title = ucfirst($name);
                }

                $options = array_merge([
                    'title' => $title,
                    'aria-label' => $title,
                    'data-pjax' => '0',
                ], $additionalOptions, $this->buttonOptions);

                $icon = Html::tag('span', '', ['class' => "glyphicon glyphicon-$iconName"]);

                return Html::a($icon, $url, $options);
            };
        }
    }

    /**
     * Creates a URL for the given action and arClass. This method is called for each button and each row.
     *
     * @param string $action the button name (or action ID)
     * @param ActiveRecordInterface $arClass the data arClass
     * @param mixed $key the key associated with the data arClass
     * @param int $index the current row index
     *
     * @return string the created URL
     */
    public function createUrl(string $action, ActiveRecordInterface $arClass, $key, int $index): string
    {
        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $arClass, $key, $index, $this);
        }

        $params = is_array($key) ? $key : ['id' => (string) $key];

        return $this->urlGenerator->generate($action, $params);
    }

    protected function renderDataCellContent($arClass, $key, int $index): string
    {
        return preg_replace_callback('/{([\w\-\/]+)}/', function ($matches) use ($arClass, $key, $index) {
            $name = $matches[1];

            if (isset($this->visibleButtons[$name])) {
                $isVisible = $this->visibleButtons[$name] instanceof Closure
                    ? call_user_func($this->visibleButtons[$name], $arClass, $key, $index)
                    : $this->visibleButtons[$name];
            } else {
                $isVisible = true;
            }

            if ($isVisible && isset($this->buttons[$name])) {
                $url = $this->createUrl($name, $arClass, $key, $index);
                return call_user_func($this->buttons[$name], $url, $arClass, $key);
            }

            return '';
        }, $this->template);
    }
}
