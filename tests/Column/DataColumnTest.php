<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Column;

use Nyholm\Psr7\ServerRequest;
use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\DataProvider\ArrayDataProvider;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Tests\TestCase;

final class DataColumnTest extends TestCase
{
    public function testAttribute(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['attribute()' => ['id']],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

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
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testAttributeStringFormat(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id', 'username']);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

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
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testContent(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView([
            'id',
            'username',
            [
                'attribute()' => ['total'],
                'content' => static fn ($arClass) => ($arClass['total'] * 20)/100
            ],
        ]);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">2</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">4</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">6</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">8</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">10</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">12</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">14</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">16</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">18</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testContentIsNull(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView([
            'id',
            'username',
            [
                'attribute()' => ['total'],
                'content' => static fn () => '',
            ],
        ]);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total"></td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total"></td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total"></td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total"></td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total"></td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total"></td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total"></td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total"></td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total"></td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testContentOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['attribute()' => ['id']],
                ['attribute()' => ['username'], 'contentOptions()' => [['class' => 'has-text-centered']]],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td class="has-text-centered" data-label="Username">tests 1</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td class="has-text-centered" data-label="Username">tests 2</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td class="has-text-centered" data-label="Username">tests 3</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td class="has-text-centered" data-label="Username">tests 4</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td class="has-text-centered" data-label="Username">tests 5</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td class="has-text-centered" data-label="Username">tests 6</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td class="has-text-centered" data-label="Username">tests 7</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td class="has-text-centered" data-label="Username">tests 8</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td class="has-text-centered" data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFilter(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
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
        <div id="w1-gridview" class="grid-view">

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
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFilterInputOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
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
        <div id="w1-gridview" class="grid-view">

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
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFilterOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                [
                    'attribute()' => ['id'],
                    'filterAttribute()' => ['id'],
                    'filterValueDefault()' => [0],
                    'filterOptions()' => [['class' => 'text-center']],
                ],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider)->filterModelName('testMe');

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr><tr class="filters"><td class="text-center"><input type="text" class="form-control" name="testMe[id]" value="0"></td><td>&nbsp;</td></tr>
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
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFilterValueDefault(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
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
        <div id="w1-gridview" class="grid-view">

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
        $this->assertEqualsWithoutLE($html, $gridView->render());

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                [
                    'attribute()' => ['id'],
                    'filterAttribute()' => ['id'],
                    'filterValueDefault()' => [0],
                    'filterInputOptions()' => [['class' => 'has-text-centered', 'maxlength' => '5']],
                ],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider)->filterModelName('testMe')->frameworkCss(GridView::BULMA);

        $html = <<<'HTML'
        <div id="w2-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr><tr class="filters"><td><input type="text" class="has-text-centered input" name="testMe[id]" value="0" maxlength="5"></td><td>&nbsp;</td></tr>
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
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFooter(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['attribute()' => ['id']],
                ['attribute()' => ['username']],
                ['attribute()' => ['total'], 'footer()' => ['90']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider)->showFooter();

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        </thead>
        <tfoot>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>90</td></tr>
        </tfoot>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">10</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">20</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">30</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">40</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">50</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">60</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">70</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">80</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">90</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFooterOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['attribute()' => ['id']],
                ['attribute()' => ['username']],
                ['attribute()' => ['total'], 'footer()' => ['90'], 'footerOptions()' => [['class' => 'has-text-link']]],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider)->showFooter();

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        </thead>
        <tfoot>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td class="has-text-link">90</td></tr>
        </tfoot>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">10</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">20</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">30</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">40</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">50</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">60</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">70</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">80</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">90</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testLabel(): void
    {
        $dataProvider = new ArrayDataProvider();
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
        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id', 'username', 'total']);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }

    public function testLabelOptions(): void
    {
        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['label()' => ['id'], 'labelOptions()' => [['class' => 'has-text-danger']]],
                ['label()' => ['username']],
                ['label()' => ['total']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <tr><th class="has-text-danger">id</th><th>username</th><th>total</th></tr>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }

    public function testNotEncodeLabel(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['attribute()' => ['id'], 'label()' => ['<id>'], 'dataLabel()' => ['Id']],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>&lt;id&gt;</th><th>Username</th></tr>
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
        $this->assertEqualsWithoutLE($html, $gridView->render());

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                [
                    'attribute()' => ['id'],
                    'label()' => ['<i class="fas fa-home>id</id>'],
                    'dataLabel()' => ['id'],
                    'notEncodeLabel()' => []
                ],
                ['attribute()' => ['username']],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w2-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th><i class="fas fa-home>id</id></th><th>Username</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="id">1</td><td data-label="Username">tests 1</td></tr>
        <tr data-key="1"><td data-label="id">2</td><td data-label="Username">tests 2</td></tr>
        <tr data-key="2"><td data-label="id">3</td><td data-label="Username">tests 3</td></tr>
        <tr data-key="3"><td data-label="id">4</td><td data-label="Username">tests 4</td></tr>
        <tr data-key="4"><td data-label="id">5</td><td data-label="Username">tests 5</td></tr>
        <tr data-key="5"><td data-label="id">6</td><td data-label="Username">tests 6</td></tr>
        <tr data-key="6"><td data-label="id">7</td><td data-label="Username">tests 7</td></tr>
        <tr data-key="7"><td data-label="id">8</td><td data-label="Username">tests 8</td></tr>
        <tr data-key="8"><td data-label="id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testNotSorting(): void
    {
        GridView::counter(0);

        $request = new ServerRequest('GET', '/admin/index');
        $this->urlMatcher->match($request);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $dataProvider->getSort()->attributes([
            'id',
            'username',
        ]);

        $gridView = $this->createGridView(['id', 'username', 'total']);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th><a href="/admin/index?page=1&amp;pagesize=10&amp;sort=id" data-sort="id">Id</a></th><th><a href="/admin/index?page=1&amp;pagesize=10&amp;sort=username" data-sort="username">Username</a></th><th>Total</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">10</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">20</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">30</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">40</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">50</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">60</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">70</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">80</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">90</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());

        $request = new ServerRequest('GET', '/admin/index');
        $this->urlMatcher->match($request);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $dataProvider->getSort()->attributes([
            'id',
            'username',
        ]);

        $gridView = $this->createGridView(['id', ['attribute()' => ['username'], 'notSorting()' => []], 'total']);
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w2-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th><a href="/admin/index?page=1&amp;pagesize=10&amp;sort=id" data-sort="id">Id</a></th><th>Username</th><th>Total</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">10</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">20</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">30</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">40</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">50</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">60</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">70</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">80</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">90</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }

    public function testNotVisible(): void
    {
        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                ['label()' => ['id']],
                ['label()' => ['username']],
                ['label()' => ['total'], 'notVisible()' => []],
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);
        $this->assertStringNotContainsString('total', $gridView->render());
    }

    public function testSortLinkOptions(): void
    {
        GridView::counter(0);

        $request = new ServerRequest('GET', '/admin/index');
        $this->urlMatcher->match($request);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $dataProvider->getSort()->attributes([
            'id',
            'username',
        ]);

        $gridView = $this->createGridView(
            [
                ['attribute()' => ['id'], 'sortLinkOptions()' => [['class' => 'testMe']]],
                'username',
                'total'
            ]
        );
        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th><a class="testMe" href="/admin/index?page=1&amp;pagesize=10&amp;sort=id" data-sort="id">Id</a></th><th><a href="/admin/index?page=1&amp;pagesize=10&amp;sort=username" data-sort="username">Username</a></th><th>Total</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">10</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">20</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">30</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">40</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">50</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">60</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">70</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">80</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">90</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }

    public function testValue(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                'id',
                'username',
                [
                    'attribute()' => ['total'],
                    'value' => static fn ($arClass): string => $arClass['total'] === '50'
                        ? '*' . $arClass['total'] . '*' : $arClass['total'],
                ],
            ]
        );

        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">10</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">20</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">30</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">40</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">*50*</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">60</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">70</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">80</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">90</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(
            [
                'id',
                'username',
                [
                    'attribute()' => ['total'],
                    'value' => 'id',
                ],
            ]
        );

        $gridView = $gridView->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w2-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th><th>Total</th></tr>
        </thead>
        <tbody>
        <tr data-key="0"><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">1</td></tr>
        <tr data-key="1"><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">2</td></tr>
        <tr data-key="2"><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">3</td></tr>
        <tr data-key="3"><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">4</td></tr>
        <tr data-key="4"><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">5</td></tr>
        <tr data-key="5"><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">6</td></tr>
        <tr data-key="6"><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">7</td></tr>
        <tr data-key="7"><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">8</td></tr>
        <tr data-key="8"><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertStringContainsString($html, $gridView->render());
    }
}
