<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Column;

use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\DataProvider\ArrayDataProvider;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Tests\TestCase;

final class DataColumnTest extends TestCase
{
    public function testAttribute(): void
    {
        $dataProvider = new ArrayDataProvider($this->pagination, $this->sort);
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['attribute()' => ['id']],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }

    public function testFilter(): void
    {
        $dataProvider = new ArrayDataProvider($this->pagination, $this->sort);
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                [
                    'attribute()' => ['id'],
                    'filter()' => ['<input type="text" class="form-control" name="testMe[id]">'],
                    'filterAttribute()' => ['id'],
                ],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider)->filterModelName('testMe');

        $html = <<<'HTML'
        <div id="w2-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr><tr class="filters"><td><input type="text" class="form-control" name="testMe[id]"></td><td>&nbsp;</td></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;

        $this->assertSame($html, $gridView->render());
    }

    public function testFilterInputOptions(): void
    {
        $dataProvider = new ArrayDataProvider($this->pagination, $this->sort);
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                [
                    'attribute()' => ['id'],
                    'filterAttribute()' => ['id'],
                    'filterValueDefault()' => [0],
                    'filterInputOptions()' => [['class' => 'text-center', 'maxlength' => '5']],
                ],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider)->filterModelName('testMe');

        $html = <<<'HTML'
        <div id="w3-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr><tr class="filters"><td><input type="text" class="text-center form-control" name="testMe[id]" value="0" maxlength="5"></td><td>&nbsp;</td></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;

        $this->assertSame($html, $gridView->render());
    }

    public function testFilterValueDefault(): void
    {
        $dataProvider = new ArrayDataProvider($this->pagination, $this->sort);
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                [
                    'attribute()' => ['id'],
                    'filterAttribute()' => ['id'],
                    'filterValueDefault()' => [0],
                ],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider)->filterModelName('testMe');

        $html = <<<'HTML'
        <div id="w4-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr><tr class="filters"><td><input type="text" class="form-control" name="testMe[id]" value="0"></td><td>&nbsp;</td></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;

        $this->assertSame($html, $gridView->render());
    }

    public function testAttributeStringFormat(): void
    {
        $dataProvider = new ArrayDataProvider($this->pagination, $this->sort);
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id', 'username']);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }

    public function testLabel(): void
    {
        $dataProvider = new ArrayDataProvider($this->pagination, $this->sort);
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['label()' => ['id']],
                ['label()' => ['username']],
                ['label()' => ['total']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <tr><th>id</th><th>username</th><th>total</th></tr>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }

    public function testLabelEmpty(): void
    {
        $dataProvider = new ArrayDataProvider($this->pagination, $this->sort);
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id', 'username', 'total']);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }
}
