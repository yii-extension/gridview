<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use JsonException;
use Yii\Extension\GridView\GridView;
use Yiisoft\Html\Html;

/**
 * Column is the base class of all {@see GridView} column classes.
 *
 * For more details and usage information on Column, see the [guide article on data widgets](guide:output-data-widgets).
 */
class Column
{
    /**
     * @var GridView the grid view object that owns this column.
     */
    public GridView $grid;

    /**
     * @var string the header cell content. Note that it will not be HTML-encoded.
     */
    public string $header = '';

    /**
     * @var string the footer cell content. Note that it will not be HTML-encoded.
     */
    public string $footer = '';

    /**
     * @var callable This is a callable that will be used to generate the content of each cell.
     * The signature of the function should be the following: `function ($arClass, $key, $index, $column)`.
     * Where `$arClass`, `$key`, and `$index` refer to the arClass, key and index of the row currently being rendered
     * and `$column` is a reference to the {@see Column} object.
     */
    public $content;

    /**
     * @var bool whether this column is visible. Defaults to true.
     */
    public bool $visible = true;

    /**
     * @var array the HTML attributes for the column group tag.
     * @see Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $options = [];

    protected array $headerOptions = [];

    /**
     * @var array|Closure the HTML attributes for the data cell tag. This can either be an array of
     * attributes or an anonymous function ([[Closure]]) that returns such an array.
     * The signature of the function should be the following: `function ($arClass, $key, $index, $column)`.
     * Where `$arClass`, `$key`, and `$index` refer to the arClass, key and index of the row currently being rendered
     * and `$column` is a reference to the [[Column]] object.
     * A function may be used to assign different attributes to different rows based on the data in that row.
     *
     * @see Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $contentOptions = [];

    /**
     * @var array the HTML attributes for the footer cell tag.
     * @see Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $footerOptions = [];

    /**
     * @var array the HTML attributes for the filter cell tag.
     * @see Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $filterOptions = [];

    /**
     * Renders the header cell.
     */
    public function renderHeaderCell(): string
    {
        return Html::tag('th', $this->renderHeaderCellContent(), array_merge($this->headerOptions, ['encode' => false]));
    }

    /**
     * Renders the footer cell.
     */
    public function renderFooterCell(): string
    {
        return Html::tag('td', $this->renderFooterCellContent(), array_merge($this->footerOptions, ['encode' => false]));
    }

    /**
     * Renders a data cell.
     *
     * @param mixed $arClass the data arClass being rendered
     * @param mixed $key the key associated with the data arClass
     * @param int $index the zero-based index of the data item among the item array returned by
     * {@see GridView::dataProvider}.
     *
     * @return string the rendering result
     * @throws JsonException
     */
    public function renderDataCell($arClass, $key, int $index): string
    {
        if ($this->contentOptions instanceof Closure) {
            $options = call_user_func($this->contentOptions, $arClass, $key, $index, $this);
        } else {
            $options = $this->contentOptions;
        }

        return Html::tag('td', (string) $this->renderDataCellContent($arClass, $key, $index), array_merge($options, ['encode' => false]));
    }

    /**
     * Renders the filter cell.
     */
    public function renderFilterCell(): string
    {
        return Html::tag('td', $this->renderFilterCellContent(), array_merge($this->filterOptions, ['encode' => false]));
    }

    /**
     * Renders the header cell content.
     *
     * The default implementation simply renders {@see header}.
     * This method may be overridden to customize the rendering of the header cell.
     *
     * @return string the rendering result
     */
    protected function renderHeaderCellContent(): string
    {
        return trim($this->header) !== '' ? $this->header : $this->getHeaderCellLabel();
    }

    /**
     * Returns header cell label.
     * This method may be overridden to customize the label of the header cell.
     *
     * @return string label
     */
    protected function getHeaderCellLabel(): string
    {
        return $this->grid->emptyCell;
    }

    /**
     * Renders the footer cell content.
     *
     * The default implementation simply renders {@see footer}.
     * This method may be overridden to customize the rendering of the footer cell.
     *
     * @return string the rendering result
     */
    protected function renderFooterCellContent(): string
    {
        return trim($this->footer) !== '' ? $this->footer : $this->grid->emptyCell;
    }

    /**
     * Renders the data cell content.
     *
     * @param mixed $arClass the data arClass
     * @param mixed $key the key associated with the data arClass
     * @param int $index the zero-based index of the data arClass among the arClasss array returned by
     * {@see GridView::dataProvider}.
     *
     * @return string the rendering result
     */
    protected function renderDataCellContent($arClass, $key, int $index): string
    {
        if ($this->content !== null) {
            return call_user_func($this->content, $arClass, $key, $index, $this);
        }

        return $this->grid->emptyCell;
    }

    /**
     * Renders the filter cell content.
     *
     * The default implementation simply renders a space.
     * This method may be overridden to customize the rendering of the filter cell (if any).
     *
     * @return string the rendering result
     */
    protected function renderFilterCellContent(): string
    {
        return $this->grid->emptyCell;
    }

    /**
     * @param array $headerOptions the HTML attributes for the header cell tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function headerOptions(array $headerOptions): self
    {
        $new = clone $this;
        $new->headerOptions = $headerOptions;

        return $new;
    }

    /**
     * @param GridView $grid the grid view object that owns this column.
     *
     * @return $this
     */
    public function grid(GridView $grid): self
    {
        $new = clone $this;
        $new->grid = $grid;

        return $this;
    }
}
