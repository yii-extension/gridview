<?php

declare(strict_types=1);

namespace Yii\Extension\GridView;

use Closure;
use Yii\Extension\GridView\Helper\Html;
use Yii\Extension\GridView\Widget\BaseListView;
use Yiisoft\Arrays\ArrayHelper;

/**
 * The ListView widget is used to display data from data provider. Each data model is rendered using the view specified.
 *
 * For more details and usage information on ListView,
 * see the [guide article on data widgets](guide:output-data-widgets).
 */
final class ListView extends BaseListView
{
    protected array $options = ['class' => 'list-view'];
    private Closure $afterItem;
    private Closure $beforeItem;
    /** @var string|callable|null */
    private $itemView = null;
    private array $itemViewOptions = [];
    private string $separator = "\n";
    private array $viewParams = [];

    /**
     * @param Closure $afterItem an anonymous function that is called once AFTER rendering each data model.
     *
     * It should have the same signature as {@see beforeItem}.
     *
     * The return result of the function will be rendered directly.
     *
     * Note: If the function returns `null`, nothing will be rendered after the item.
     *
     * @return $this
     *
     * @see renderAfterItem
     */
    public function afterItem(Closure $afterItem): self
    {
        $this->afterItem = $afterItem;

        return $this;
    }

    /**
     * @param Closure $beforeItem an anonymous function that is called once BEFORE rendering each data model.
     *
     * It should have the following signature:
     *
     * ```php
     * function ($data, $key, $index, $widget)
     * ```
     *
     * - `$data`: the current data model being rendered.
     * - `$key`: the key value associated with the current data model.
     * - `$index`: the zero-based index of the data model in the model array returned by {@see dataProvider}.
     * - `$widget`: the ListView object.
     *
     * The return result of the function will be rendered directly.
     *
     * Note: If the function returns `null`, nothing will be rendered before the item.
     *
     * @see renderBeforeItem
     */
    public function beforeItem(Closure $beforeItem): self
    {
        $this->beforeItem = $beforeItem;

        return $this;
    }

    /**
     * @param array $itemViewOptions the HTML attributes for the container of the rendering result of each data model.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function itemViewOptions(array $itemViewOptions): self
    {
        $new = clone $this;
        $new->itemViewOptions = $itemViewOptions;

        return $new;
    }

    /**
     * @param string|callable $itemView the name of the view for rendering each data item, or a callback (e.g. an
     * anonymous function) for rendering each data item. If it specifies a view name, the following variables will be
     * available in the view:
     *
     * - `$data`: mixed, the data model.
     * - `$key`: mixed, the key value associated with the data item.
     * - `$index`: integer, the zero-based index of the data item in the items array returned by {@see dataProvider}.
     * - `$widget`: ListView, this widget instance.
     *
     * Note that the view name is resolved into the view file by the current context of the {@see view} object.
     *
     * If this property is specified as a callback, it should have the following signature:
     *
     * ```php
     * function ($data, $key, $index, $widget)
     * ```
     *
     * @return $this
     */
    public function itemView($itemView): self
    {
        $new = clone $this;
        $new->itemView = $itemView;

        return $new;
    }

    /**
     * @param string $separator the HTML code to be displayed between any two consecutive items.
     *
     * @return $this
     */
    public function separator(string $separator): self
    {
        $new = clone $this;
        $new->separator = $separator;

        return $new;
    }

    /**
     * @param array $viewParams additional parameters to be passed to {@see itemView} when it is being rendered.
     *
     * This property is used only when {@see itemView} is a string representing a view name.
     *
     * @return $this
     */
    public function viewParams(array $viewParams): self
    {
        $new = clone $this;
        $new->viewParams = $viewParams;

        return $new;
    }

    /**
     * Renders all data models.
     *
     * @return string the rendering result
     */
    protected function renderItems(): string
    {
        $models = $this->dataProvider->getARClasses();
        $keys = $this->dataProvider->getKeys();
        $rows = [];

        /** @var array<array-key, array|object> $models */
        foreach (array_values($models) as $index => $model) {
            /** @var int */
            $key = $keys[$index];

            if (($before = $this->renderBeforeItem($model, $key, $index)) !== '') {
                $rows[] = $before;
            }

            $rows[] = $this->renderItem($model, $key, $index);

            if (($after = $this->renderAfterItem($model, $key, $index)) !== '') {
                $rows[] = $after;
            }
        }

        return implode($this->separator, $rows);
    }

    /**
     * Renders a single data model.
     *
     * @param array|object $model the data model to be rendered
     * @param mixed $key the key value associated with the data model
     * @param int $index the zero-based index of the data model in the model array returned by {@see dataProvider}.
     *
     * @return string the rendering result
     */
    protected function renderItem($model, $key, int $index): string
    {
        if ($this->itemView === null) {
            $content = (string) $key;
        } elseif (is_string($this->itemView)) {
            $content = $this->webView->render(
                $this->itemView,
                array_merge(
                    [
                        'model' => $model,
                        'key' => $key,
                        'index' => $index,
                        'widget' => $this,
                    ],
                    $this->viewParams
                )
            );
        } else {
            $content = (string) call_user_func($this->itemView, $model, $key, $index, $this);
        }

        /** @var string */
        $tag = ArrayHelper::remove($this->itemViewOptions, 'tag', 'div');

        if ($content !== '' && $this->frameworkCss === static::BULMA) {
            $this->itemViewOptions['data-key'] = is_array($key)
                ? json_encode($key, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) $key;
        }

        return $this->html->tag($tag, $content, $this->itemViewOptions);
    }

    /**
     * Calls {@see afterItem} closure, returns execution result.
     *
     * If {@see afterItem} is not a closure, `null` will be returned.
     *
     * @param array|object $model the data model to be rendered.
     * @param mixed $key the key value associated with the data model.
     * @param int $index the zero-based index of the data model in the model array returned by {@see dataProvider}.
     *
     * @return string {@see afterItem} call result when {@see afterItem} is not a closure.
     *
     * @see afterItem
     */
    private function renderAfterItem($model, $key, $index)
    {
        $result = '';

        if (!empty($this->afterItem)) {
            $result = (string) call_user_func($this->afterItem, $model, $key, $index, $this);
        }

        return $result;
    }

    /**
     * Calls {@see beforeItem} closure, returns execution result.
     *
     * If {@see beforeItem} is not a closure, `null` will be returned.
     *
     * @param array|object $model the data model to be rendered.
     * @param mixed $key the key value associated with the data model.
     * @param int $index the zero-based index of the data model in the model array returned by {@see dataProvider}.
     * @return string {@see beforeItem} call result or `null` when {@see beforeItem} is not a closure.
     *
     * @see beforeItem
     *
     */
    private function renderBeforeItem($model, $key, $index): string
    {
        $result = '';

        if (!empty($this->beforeItem)) {
            $result = (string) call_user_func($this->beforeItem, $model, $key, $index, $this);
        }

        return $result;
    }
}
