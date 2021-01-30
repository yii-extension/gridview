<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Widget;

use Yii\Extension\GridView\Widget;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yiisoft\Db\Data\Sort;
use Yiisoft\Html\Html;

/**
 * LinkSorter renders a list of sort links for the given sort definition.
 *
 * LinkSorter will generate a hyperlink for every attribute declared in {@see sort}.
 *
 * For more details and usage information on LinkSorter, see the [guide article on sorting](guide:output-sorting).
 */
final class LinkSorter extends Widget
{
    /**
     * @var Sort the sort definition
     */
    public Sort $sort;

    /**
     * @var array list of the attributes that support sorting. If not set, it will be determined
     * using {@see Sort::attributes}.
     */
    public $attributes;

    /**
     * @var array HTML attributes for the sorter container tag.
     * @see \yii\helpers\Html::ul() for special attributes.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'sorter'];

    /**
     * @var array HTML attributes for the link in a sorter container tag which are passed to {@see Sort::link()}.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public $linkOptions = [];

    /**
     * Executes the widget.
     * This method renders the sort links.
     */
    public function run(): string
    {
        if ($this->sort === null) {
            throw new InvalidConfigException('The "sort" property must be set.');
        }

        return $this->renderSortLinks();
    }

    /**
     * Renders the sort links.
     *
     * @return string the rendering result
     */
    protected function renderSortLinks(): string
    {
        $attributes = empty($this->attributes) ? array_keys($this->sort->attributes) : $this->attributes;
        $links = [];

        foreach ($attributes as $name) {
            $links[] = $this->sort->link($name, $this->linkOptions);
        }

        return Html::ul($links, array_merge($this->options, ['encode' => false]));
    }
}
