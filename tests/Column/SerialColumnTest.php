<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Column;

use Yii\Extension\GridView\Column\SerialColumn;
use Yii\Extension\GridView\DataProvider\ArrayDataProvider;
use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Tests\TestCase;

final class SerialColumnTest extends TestCase
{
    public function testRender(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView([
            [
                '__class' => SerialColumn::class,
                'label()' => ['x'],
            ],
        ]);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>x</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="x">1</td></tr>
        <tr data-key="1"><td data-label="x">2</td></tr>
        <tr data-key="2"><td data-label="x">3</td></tr>
        <tr data-key="3"><td data-label="x">4</td></tr>
        <tr data-key="4"><td data-label="x">5</td></tr>
        <tr data-key="5"><td data-label="x">6</td></tr>
        <tr data-key="6"><td data-label="x">7</td></tr>
        <tr data-key="7"><td data-label="x">8</td></tr>
        <tr data-key="8"><td data-label="x">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }
}
