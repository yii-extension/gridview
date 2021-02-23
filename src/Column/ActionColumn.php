<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use Yii\Extension\GridView\Helper\Html;
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
 *         '__class' => ActionColumn::class,
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
    private array $buttons = [];
    private array $buttonOptions = [];
    private array $headerOptions = [];
    private string $primaryKey = 'id';
    private string $template = '{view} {update} {delete}';
    /** @var callable|null $urlCreator */
    private $urlCreator = null;
    private array $visibleButtons = [];

    public function __construct(Html $html, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($html, $urlGenerator);

        $this->loadDefaultButtons();
    }

    /**
     * @param array $buttons button rendering callbacks. The array keys are the button names (without curly brackets),
     * and the values are the corresponding button rendering callbacks. The callbacks should use the following
     * signature:
     *
     * ```php
     * [
     *     buttons() => [
     *         'action' => function (string $url, $arClass, int $key) {
     *             // return the button HTML code
     *         }
     *     ],
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
     *     buttons() = [
     *         'update' => function (string $url, $arClass, $key) {
     *             return $arClass->status === 'editable' ? Html::a('Update', $url) : '';
     *         },
     *     ],
     * ],
     * ```
     *
     * @return $this
     */
    public function buttons(array $buttons): self
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * @param array $buttonOptions HTML options to be applied to the default button, see {@see loadDefaultButton()}.
     *
     * @return self
     */
    public function buttonOptions(array $buttonOptions): self
    {
        $this->buttonOptions = $buttonOptions;

        return $this;
    }

    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Indicates which is the primaryKey of the data to be used to generate the url automatically.
     *
     * @param string $primaryKey by default the primaryKey is `id`.
     *
     * @return self
     */
    public function primaryKey(string $primaryKey): self
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * @param string $template the template used for composing each cell in the action column. Tokens enclosed within
     * curly brackets are treated as controller action IDs (also called *button names* in the context of action column).
     * They will be replaced by the corresponding button rendering callbacks specified in {@see buttons}. For example,
     * the token `{view}` will be replaced by the result of the callback `buttons['view']`. If a callback cannot be
     * found, the token will be replaced with an empty string.
     *
     * As an example, to only have the view, and update button you can add the ActionColumn to your GridView columns as
     * follows:
     *
     * ```php
     * [
     *     '__class' => ActionColumn::class,
     *     'template()' => ['{view} {update} {delete}'],
     * ],
     * ```
     *
     * @return $this
     *
     * {@see buttons}
     */
    public function template(string $template): self
    {
        $result = preg_match_all('/{([\w\-\/]+)}/', $template, $matches);

        if ($result > 0 && !empty($matches[1])) {
            $this->buttons = array_intersect_key($this->buttons, array_flip($matches[1]));
        }

        $this->template = $template;

        return $this;
    }

    /**
     * @param callable $urlCreator a callback that creates a button URL using the specified model information.
     *
     * The signature of the callback should be the same as that of {@see createUrl()}. It can accept additional
     * parameter, which refers to the column instance itself:
     * ```php
     * [
     *     'urlCreator()' => [
     *         'action' => function (string $action, $arClass, mixed $key, int $index) {
     *             return string;
     *         }
     *     ],
     * }
     * ```
     *
     * If this property is not set, button URLs will be created using {@see createUrl()}.
     *
     * @return $this
     */
    public function urlCreator(callable $urlCreator): self
    {
        $this->urlCreator = $urlCreator;

        return $this;
    }

    /**
     * @var array $visibleButtons visibility conditions for each button. The array keys are the button names (without
     * curly brackets), and the values are the boolean true/false or the anonymous function. When the button name is not
     * specified in this array it will be shown by default.
     *
     * The callbacks must use the following signature:
     *
     * ```php
     * [
     *     visibleButtons() => [
     *         update => [
     *             function ($arClass, $key, int $index) {
     *                 return $arClass->status === 'editable';
     *             }
     *         ],
     *     ],
     * }
     * ```
     *
     * Or you can pass a boolean value:
     *
     * ```php
     * [
     *     visibleButtons() => [
     *         'update' => true,
     *     ],
     * ],
     * ```
     *
     * @return $this
     */
    public function visibleButtons(array $visibleButtons): self
    {
        $this->visibleButtons = $visibleButtons;

        return $this;
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
        return preg_replace_callback('/{([\w\-\/]+)}/', function (array $matches) use ($arClass, $key, $index): string {
            /** @var string */
            $name = $matches[1];

            if (isset($this->visibleButtons[$name])) {
                /** @var bool */
                $isVisible = $this->visibleButtons[$name] instanceof Closure
                    ? $this->visibleButtons[$name]($arClass, $key, $index)
                    : $this->visibleButtons[$name];
            } else {
                $isVisible = true;
            }

            if ($isVisible && isset($this->buttons[$name])) {
                $url = $this->createUrl($name, $arClass, $key, $index);

                /** @psalm-suppress MixedFunctionCall */
                return (string) $this->buttons[$name]($url, $arClass, $key);
            }

            return '';
        }, $this->template);
    }

    /**
     * Initializes the default button rendering callbacks.
     */
    private function loadDefaultButtons(): void
    {
        $defaultButtons = ([
            ['view','&#128065;', []],
            ['update', '&#128393;', []],
            [
                'delete',
                '&#128465;',
                [
                    'data-confirm' => 'Are you sure you want to delete this item?',
                    'data-method' => 'post',
                ],
            ],
        ]);

        foreach ($defaultButtons as $defaultButton) {
            $this->createDefaultButton($defaultButton[0], $defaultButton[1], $defaultButton[2]);
        }
    }

    /**
     * Initializes the default button rendering callback for single button.
     *
     * @param string $name Button name as it's written in template.
     * @param string $iconName The part of Bootstrap glyphicon class that makes it unique.
     * @param array $additionalOptions Array of additional options.
     *
     * @return void
     */
    private function createDefaultButton(string $name, string $iconName, array $additionalOptions = []): void
    {
        $title = '';

        if (!isset($this->buttons[$name]) && strpos($this->template, '{' . $name . '}') !== false) {
            $this->buttons[$name] = function (?string $url) use ($name, $iconName, $additionalOptions, $title): string {
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
                }

                $options = array_merge(
                    [
                        'title' => $title,
                        'aria-label' => $title,
                        'data-name' => $name,
                    ],
                    $additionalOptions,
                    $this->buttonOptions
                );

                $icon = $this->html->tag('span', $iconName);

                return $this->html->a($icon, $url, $options);
            };
        }
    }

    /**
     * Creates a URL for the given action and arClass. This method is called for each button and each row.
     *
     * @param string $action the button name (or action ID).
     * @param array|object $arClass the data arClass.
     * @param mixed $key the key associated with the data arClass.
     * @param int $index the current row index.
     *
     * @return string the created URL.
     */
    private function createUrl(string $action, $arClass, $key, int $index): string
    {
        if ($this->urlCreator !== null) {
            /** @psalm-suppress MixedFunctionCall */
            return (string) call_user_func($this->urlCreator, $action, $arClass, $key, $index, $this);
        }

        $params = is_array($key) ? $key : [$this->primaryKey => $key];

        return $this->urlGenerator->generate($action, $params);
    }
}
