<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Yii\Extension\GridView\Helper\Html;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * SerialColumn displays a column of row numbers (1-based).
 *
 * To add a SerialColumn to the {@see GridView}, add it to the {@see GridView::columns|columns} configuration as
 * follows:
 *
 * ```php
 * 'columns' => [
 *     // ...
 *     [
 *         '__class' => SerialColumn::class,
 *         // you may configure additional properties here
 *     ],
 * ]
 * ```
 *
 * For more details and usage information on SerialColumn, see the:
 * [guide article on data widgets](guide:output-data-widgets).
 */
class SerialColumn extends Column
{
    public string $header = '#';

    public function __construct(Html $html, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($html, $urlGenerator);
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
        $pagination = $this->grid->getPagination();

        if ($pagination !== null) {
            return (string) ($pagination->getOffset() + $index + 1);
        }

        return (string) ($index + 1);
    }
}
