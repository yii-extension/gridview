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
    /**
     * @var callable
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
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
    protected UrlGeneratorInterface $urlGenerator;
    protected bool $visible = true;
    private string $dataLabel = '';

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
        $this->content = $content;

        return $this;
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
        $this->contentOptions = $contentOptions;

        return $this;
    }

    public function dataLabel(string $dataLabel): self
    {
        $this->dataLabel = $dataLabel;

        return $this;
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
        $this->filterOptions = $filterOptions;

        return $this;
    }

    /**
     * @param string the footer cell content. Note that it will not be HTML-encoded.
     *
     * @return $this
     */
    public function footer(string $footer)
    {
        $this->footer = $footer;

        return $this;
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
        $this->footerOptions = $footerOptions;

        return $this;
    }

    /**
     * @param GridView $grid the grid view object that owns this column.
     *
     * @return $this
     */
    public function grid(GridView $grid): self
    {
        $this->grid = $grid;

        return $this;
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
        $this->label = $label;

        return $this;
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
        $this->labelOptions = $labelOptions;

        return $this;
    }

    /**
     * @param bool whether this column is visible. Defaults to true.
     *
     * @return $this
     */
    public function notVisible(): self
    {
        $this->visible = false;

        return $this;
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
     * @param array|object $arClass the data arClass being rendered.
     * @param mixed $key the key associated with the data arClass.
     * @param int $index the zero-based index of the data item among the item array returned by
     * {@see GridView::dataProvider}.
     *
     * @throws JsonException
     *
     * @return string the rendering result.
     */
    public function renderDataCell($arClass, $key, int $index): string
    {
        if ($this->dataLabel === '') {
            $this->dataLabel = $this->label;
        }

        if ($this->dataLabel !== '') {
            $this->contentOptions = array_merge($this->contentOptions, ['data-label' => $this->dataLabel]);
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
        $html = $this->grid->getEmptyCell();

        if (!empty($this->content)) {
            $html = (string) call_user_func($this->content, $arClass, $key, $index, $this);
        }

        return $html;
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
