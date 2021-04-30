<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Column;

use Yii\Extension\GridView\Column\RadioButtonColumn;
use Yii\Extension\GridView\DataProvider\ArrayDataProvider;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Tests\TestCase;

final class RadioButtonColumnTest extends TestCase
{
    public function testContentIsEmpty(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView([
            [
                'class' => RadioButtonColumn::class,
                'content' => static fn () => '',
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
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        <tr><td data-label="x"></td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testName(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());
        $gridView = $this->createGridView([
            [
                'class' => RadioButtonColumn::class,
                'name()' => ['testMe'],
            ],
        ]);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>&nbsp;</th></tr>
        </thead>
        <tbody>
        <tr><td><input type="radio" name="testMe" value="0"></td></tr>
        <tr><td><input type="radio" name="testMe" value="1"></td></tr>
        <tr><td><input type="radio" name="testMe" value="2"></td></tr>
        <tr><td><input type="radio" name="testMe" value="3"></td></tr>
        <tr><td><input type="radio" name="testMe" value="4"></td></tr>
        <tr><td><input type="radio" name="testMe" value="5"></td></tr>
        <tr><td><input type="radio" name="testMe" value="6"></td></tr>
        <tr><td><input type="radio" name="testMe" value="7"></td></tr>
        <tr><td><input type="radio" name="testMe" value="8"></td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testNameEmpty(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());
        $gridView = $this->createGridView([
            [
                'class' => RadioButtonColumn::class,
                'name()' => [''],
            ],
        ]);
        $gridView = $gridView->dataProvider($dataProvider);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "name" property it cannot be empty.');

        $gridView->render();
    }

    public function testRender(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());
        $gridView = $this->createGridView([
            [
                'class' => RadioButtonColumn::class,
            ],
        ]);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>&nbsp;</th></tr>
        </thead>
        <tbody>
        <tr><td><input type="radio" name="radioButtonSelection" value="0"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="1"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="2"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="3"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="4"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="5"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="6"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="7"></td></tr>
        <tr><td><input type="radio" name="radioButtonSelection" value="8"></td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testValue(): void
    {
        $column = $this->radioButtonColumn;
        $html = <<<'HTML'
        <td><input type="radio" name="radioButtonSelection" value="1"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 1, 0));

        $html = <<<'HTML'
        <td><input type="radio" name="radioButtonSelection" value="42"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 42, 0));

        $html = <<<'HTML'
        <td><input type="radio" name="radioButtonSelection" value="[1,42]"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], [1,42], 0));

        $column = $this->radioButtonColumn->radioOptions(['value' => 42])->grid($this->createGridView());
        $this->assertStringNotContainsString('value="1"', $column->renderDataCell([], 1, 0));

        $html = <<<'HTML'
        <td><input type="radio" name="radioButtonSelection" value="42"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 1, 0));
    }
}
