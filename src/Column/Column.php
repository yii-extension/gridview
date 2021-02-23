<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use JsonException;
use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Helper\Html;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Column is the base class of all {@see GridView} column classes.
 *
 * For more details and usage information on Column, see the [guide article on data widgets](guide:output-data-widgets).
 */
class Column
{
    protected $content;
    protected array $contentOptions = [];
    protected array $filterOptions = [];
    protected string $footer = '';
    protected array $footerOptions = [];
    protected Html $html;
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected GridView $grid;
    protected string $label = '';
    protected array $labelOptions = [];
    protected array $options = [];
    protected UrlGeneratorInterface $urlGenerator;
    protected bool $visible = true;

    public function __construct(Html $html, UrlGeneratorInterface $urlGenerator)
    {
        $this->html = $html;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param callable This is a callable that will be used to generate the content of each cell.
     *
     * The signature of the function should be the following: `function ($arClass, $key, $index, $column)`.
     *
     * Where `$arClass`, `$key`, and `$index` refer to the arClass, key and index of the row currently being rendered
     * and `$column` is a reference to the {@see Column} object.
     *
     * @return $this
     */
    public function content(callable $content): self
    {
        $new = clone $this;
        $new->content = $content;

        return $new;
    }

    /**
     * @param array the HTML attributes for the data cell tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function contentOptions(array $contentOptions): self
    {
        $new = clone $this;
        $new->contentOptions = $contentOptions;

        return $new;
    }

    /**
     * @param array the HTML attributes for the filter cell tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function filterOptions(array $filterOptions): self
    {
        $new = clone $this;
        $new->filterOptions = $filterOptions;

        return $new;
    }

    /**
     * @param string the footer cell content. Note that it will not be HTML-encoded.
     *
     * @return $this
     */
    public function footer(string $footer)
    {
        $new = clone $this;
        $new->footer = $footer;

        return $new;
    }

    /**
     * @param array the HTML attributes for the footer cell tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function footerOptions(array $footerOptions)
    {
        $new = clone $this;
        $new->footerOptions = $footerOptions;

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

        return $new;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @param string label to be displayed in the {@see header|header cell} and also to be used as the sorting link
     * label when sorting is enabled for this column.
     *
     * If it is not set and the active record classes provided by the GridViews data provider are instances of the model
     * data, the label will be determined using Otherwise {@see Inflector::toHumanReadable()} will be used to get a
     * label.
     *
     * @return $this
     */
    public function label(string $label): self
    {
        $new = clone $this;
        $new->label = $label;

        return $new;
    }

    /**
     * @param array $labelOptions the HTML attributes for the header cell tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function labelOptions(array $labelOptions): self
    {
        $new = clone $this;
        $new->labelOptions = $labelOptions;

        return $new;
    }

    /**
     * @param bool whether this column is visible. Defaults to true.
     *
     * @return $this
     */
    public function notVisible(): self
    {
        $new = clone $this;
        $new->visible = false;

        return $new;
    }

    /**
     * @param array the HTML attributes for the column group tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function options(array $options)
    {
        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * Renders the header cell.
     */
    public function renderHeaderCell(): string
    {
        return $this->html->tag('th', $this->renderHeaderCellContent(), $this->labelOptions);
    }

    /**
     * Renders the footer cell.
     */
    public function renderFooterCell(): string
    {
        return $this->html->tag('td', $this->renderFooterCellContent(), $this->footerOptions);
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
        if ($this->label !== '') {
            $this->contentOptions = array_merge($this->contentOptions, ['data-label' => $this->label]);
        }

        return $this->html->tag(
            'td',
            $this->renderDataCellContent($arClass, $key, $index),
            $this->contentOptions,
        );
    }

    /**
     * Renders the filter cell.
     */
    public function renderFilterCell(): string
    {
        return $this->html->tag('td', $this->renderFilterCellContent(), $this->filterOptions);
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
        return trim($this->label) !== '' ? $this->label : $this->getHeaderCellLabel();
    }

    /**
     * Returns header cell label.
     * This method may be overridden to customize the label of the header cell.
     *
     * @return string label
     */
    protected function getHeaderCellLabel(): string
    {
        return $this->grid->getEmptyCell();
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
        return trim($this->footer) !== '' ? $this->footer : $this->grid->getEmptyCell();
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
        if ($this->content !== null) {
            return call_user_func($this->content, $arClass, $key, $index, $this);
        }

        return $this->grid->getEmptyCell();
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
        return $this->grid->getEmptyCell();
    }
}
