<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

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

    protected function renderDataCellContent($arClass, $key, $index): string
    {
        $pagination = $this->grid->getPagination();

        if ($pagination !== null) {
            return (string) ($pagination->getOffset() + $index + 1);
        }

        return (string) ($index + 1);
    }
}
