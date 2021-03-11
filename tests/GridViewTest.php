<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Column;

use Nyholm\Psr7\ServerRequest;
use Yii\Extension\GridView\Column\ActionColumn;
use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\DataProvider\ArrayDataProvider;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Tests\TestCase;

final class GridViewTest extends TestCase
{
    public function testAfterItemBeforeItem(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->beforeRow(static fn () =>  '<div class="testMe">')
            ->afterRow(static fn () => '</div>')
            ->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        <div class="testMe">
        <tr></tr>
        </div>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testAutoIdPrefix(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()->dataProvider($dataProvider)->autoIdPrefix('test');

        $html = <<<'HTML'
        <div id="test1-gridview" class="grid-view"><div class="empty">No results found.</div></div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testColumnsButtons(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->columns(
                [
                    'id',
                    'username',
                    'total',
                    [
                        '__class' => ActionColumn::class,
                        'label()' => ['Operations'],
                        'buttons' => [
                            'delete' => function ($url) {
                                return $this->html->a(
                                    $this->html->tag('span', '&#128465;'),
                                    $url,
                                    [
                                        'class' => 'text-danger',
                                        'data-method' => 'POST',
                                        'data-confirm' => 'Are you sure to delete this user?',
                                        'title' => 'Delete',
                                    ],
                                );
                            },
                        ],
                        'visibleButtons' => [
                            'view' => function ($arClass, $key, $index) {
                                return true;
                             }
                        ],
                    ],
                ],
            )
            ->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th><th>Total</th><th>Operations</th></tr>
        </thead>
        <tbody>
        <tr><td data-label="Id">1</td><td data-label="Username">tests 1</td><td data-label="Total">10</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/0" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">2</td><td data-label="Username">tests 2</td><td data-label="Total">20</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/1" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">3</td><td data-label="Username">tests 3</td><td data-label="Total">30</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/2" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">4</td><td data-label="Username">tests 4</td><td data-label="Total">40</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/3" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">5</td><td data-label="Username">tests 5</td><td data-label="Total">50</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/4" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">6</td><td data-label="Username">tests 6</td><td data-label="Total">60</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/5" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">7</td><td data-label="Username">tests 7</td><td data-label="Total">70</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/6" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">8</td><td data-label="Username">tests 8</td><td data-label="Total">80</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/7" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        <tr><td data-label="Id">9</td><td data-label="Username">tests 9</td><td data-label="Total">90</td><td data-label="Operations">  <a class="text-danger" href="/admin/delete/8" title="Delete" data-method="POST" data-confirm="Are you sure to delete this user?"><span>&#128465;</span></a></td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testColumnsException(): void
    {
        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"'
        );
        $gridView = GridView::widget()->columns([''])->dataProvider($dataProvider)->render();
    }

    public function testDataColumnClass(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()->dataColumnClass(DataColumn::class)->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testDataProviderEmpty(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "dataProvider" property must be set.');
        GridView::widget()->render();
    }

    public function testEncloseByContainer(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()->dataProvider($dataProvider)->encloseByContainer();

        $html = <<<'HTML'
        <div>
        <div id="w1-gridview" class="grid-view"><div class="empty">No results found.</div></div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testEncloseByContainerOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()
            ->dataProvider($dataProvider)
            ->encloseByContainer()
            ->encloseByContainerOptions(['class' => 'testMe']);

        $html = <<<'HTML'
        <div class="testMe">
        <div id="w1-gridview" class="grid-view"><div class="empty">No results found.</div></div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testEmptyCell(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->columns(['id'])
            ->dataProvider($dataProvider)
            ->emptyCell('Empty Cell')
            ->showFooter();

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tfoot>
        <tr><td>Empty Cell</td></tr>
        </tfoot>
        <tbody>
        <tr><td data-label="Id">1</td></tr>
        <tr><td data-label="Id">2</td></tr>
        <tr><td data-label="Id">3</td></tr>
        <tr><td data-label="Id">4</td></tr>
        <tr><td data-label="Id">5</td></tr>
        <tr><td data-label="Id">6</td></tr>
        <tr><td data-label="Id">7</td></tr>
        <tr><td data-label="Id">8</td></tr>
        <tr><td data-label="Id">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testEmptyText(): void
    {
        GridView::counter(0);

        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()->dataProvider($dataProvider)->emptyText('');

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view"></div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()->dataProvider($dataProvider)->emptyText('Not Found');

        $html = <<<'HTML'
        <div id="w2-gridview" class="grid-view"><div class="empty">Not Found</div></div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testEmptyTextOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()
            ->dataProvider($dataProvider)
            ->emptyText('Not Found')
            ->emptyTextOptions(['class' => 'text-danger']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view"><div class="text-danger">Not Found</div></div>
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
        $gridView = $gridView
            ->dataProvider($dataProvider)
            ->filterModelName('testMe')
            ->filterPosition(GridView::FILTER_POS_HEADER);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr class="filters"><td class="text-center"><input type="text" class="form-control" name="testMe[id]" value="0"></td><td>&nbsp;</td></tr><tr><th>Id</th><th>Username</th></tr>
        </thead>
        <tbody>
        <tr><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());

        $gridView = $gridView
            ->dataProvider($dataProvider)
            ->filterModelName('testMe')
            ->filterPosition(GridView::FILTER_POS_FOOTER)
            ->showFooter();

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr>
        </thead>
        <tfoot>
        <tr><td>&nbsp;</td><td>&nbsp;</td></tr><tr class="filters"><td class="text-center"><input type="text" class="form-control" name="testMe[id]" value="0"></td><td>&nbsp;</td></tr>
        </tfoot>
        <tbody>
        <tr><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFilterRowOptions(): void
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
        $gridView = $gridView->dataProvider($dataProvider)
            ->filterModelName('testMe')
            ->filterRowOptions(['class' => 'text-danger']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th><th>Username</th></tr><tr class="text-danger"><td class="text-center"><input type="text" class="form-control" name="testMe[id]" value="0"></td><td>&nbsp;</td></tr>
        </thead>
        <tbody>
        <tr><td data-label="Id">1</td><td data-label="Username">tests 1</td></tr>
        <tr><td data-label="Id">2</td><td data-label="Username">tests 2</td></tr>
        <tr><td data-label="Id">3</td><td data-label="Username">tests 3</td></tr>
        <tr><td data-label="Id">4</td><td data-label="Username">tests 4</td></tr>
        <tr><td data-label="Id">5</td><td data-label="Username">tests 5</td></tr>
        <tr><td data-label="Id">6</td><td data-label="Username">tests 6</td></tr>
        <tr><td data-label="Id">7</td><td data-label="Username">tests 7</td></tr>
        <tr><td data-label="Id">8</td><td data-label="Username">tests 8</td></tr>
        <tr><td data-label="Id">9</td><td data-label="Username">tests 9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFooterRowOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id']);
        $gridView = $gridView->dataProvider($dataProvider)->footerRowOptions(['class' => 'text-center'])->showFooter();

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tfoot>
        <tr class="text-center"><td>&nbsp;</td></tr>
        </tfoot>
        <tbody>
        <tr><td data-label="Id">1</td></tr>
        <tr><td data-label="Id">2</td></tr>
        <tr><td data-label="Id">3</td></tr>
        <tr><td data-label="Id">4</td></tr>
        <tr><td data-label="Id">5</td></tr>
        <tr><td data-label="Id">6</td></tr>
        <tr><td data-label="Id">7</td></tr>
        <tr><td data-label="Id">8</td></tr>
        <tr><td data-label="Id">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testFrameworkCssException(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid framework css. Valid values are: "bootstrap", "bulma".');
        $gridView = GridView::widget()->frameworkCss('NoExist');
    }

    public function testHeaderOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->dataProvider($dataProvider)
            ->header('GridView test header.')
            ->headerOptions(['class' => 'text-success']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view"><header class="text-success">GridView test header.</header>

        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testHeaderRowOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->dataProvider($dataProvider)
            ->headerRowOptions(['class' => 'text-success']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr class="text-success"></tr>
        </thead>
        <tbody>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testId(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()->dataProvider($dataProvider)->id('testMe');

        $html = <<<'HTML'
        <div id="testMe-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testLayout(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->dataProvider($dataProvider)
            ->layout("{header}\n{toolbar}\n{summary}\n{items}\n{pager}");

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        </tbody></table>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testNotShowHeader(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()->dataProvider($dataProvider)->notShowHeader();

        $html = <<<'HTML'
        <thead>
        </thead>
        HTML;
        $this->assertStringNotContainsString($html, $gridView->render());
    }

    public function testRenderEmpty(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()->dataProvider($dataProvider);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view"><div class="empty">No results found.</div></div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testRenderSummary(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()->dataProvider($dataProvider)->Summary('Summary');

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        </tbody></table>
        <div class="summary">Summary</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testRequestAttributes(): void
    {
        GridView::counter(0);

        $request = new ServerRequest('GET', '/admin/index');
        $this->urlMatcher->match($request);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->columns(['id'])
            ->currentPage(1)
            ->dataProvider($dataProvider)
            ->pageSize(5)
            ->requestAttributes(['filter' => 1]);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tbody>
        <tr><td data-label="Id">1</td></tr>
        <tr><td data-label="Id">2</td></tr>
        <tr><td data-label="Id">3</td></tr>
        <tr><td data-label="Id">4</td></tr>
        <tr><td data-label="Id">5</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-5</b> of <b>9</b> items</div>
        <nav aria-label="Pagination">
        <ul class="pagination justify-content-center mt-4">
        <li class="page-item disabled"><a class="page-link" href="/admin/index?page=1&amp;pagesize=5&amp;filter=1" data-page="1" aria-disabled="true" tabindex="-1">Previous</a></li><li class="page-item active"><a class="page-link" href="/admin/index?page=1&amp;pagesize=5&amp;filter=1" data-page="1">1</a></li>
        <li class="page-item"><a class="page-link" href="/admin/index?page=2&amp;pagesize=5&amp;filter=1" data-page="2">2</a></li><li class="page-item"><a class="page-link" href="/admin/index?page=2&amp;pagesize=5&amp;filter=1" data-page="2">Next Page</a></li>
        </ul>
        </nav>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testRequestQueryParams(): void
    {
        GridView::counter(0);

        $request = new ServerRequest('GET', '/admin/index');
        $this->urlMatcher->match($request);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->columns(['id'])
            ->currentPage(1)
            ->dataProvider($dataProvider)
            ->pageSize(5)
            ->requestQueryParams(['filter' => 1]);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tbody>
        <tr><td data-label="Id">1</td></tr>
        <tr><td data-label="Id">2</td></tr>
        <tr><td data-label="Id">3</td></tr>
        <tr><td data-label="Id">4</td></tr>
        <tr><td data-label="Id">5</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-5</b> of <b>9</b> items</div>
        <nav aria-label="Pagination">
        <ul class="pagination justify-content-center mt-4">
        <li class="page-item disabled"><a class="page-link" href="/admin/index?page=1&amp;pagesize=5&amp;filter=1" data-page="1" aria-disabled="true" tabindex="-1">Previous</a></li><li class="page-item active"><a class="page-link" href="/admin/index?page=1&amp;pagesize=5&amp;filter=1" data-page="1">1</a></li>
        <li class="page-item"><a class="page-link" href="/admin/index?page=2&amp;pagesize=5&amp;filter=1" data-page="2">2</a></li><li class="page-item"><a class="page-link" href="/admin/index?page=2&amp;pagesize=5&amp;filter=1" data-page="2">Next Page</a></li>
        </ul>
        </nav>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testRowOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id']);
        $gridView = $gridView->dataProvider($dataProvider)->rowOptions(['class' => 'text-success']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tbody>
        <tr class="text-success"><td data-label="Id">1</td></tr>
        <tr class="text-success"><td data-label="Id">2</td></tr>
        <tr class="text-success"><td data-label="Id">3</td></tr>
        <tr class="text-success"><td data-label="Id">4</td></tr>
        <tr class="text-success"><td data-label="Id">5</td></tr>
        <tr class="text-success"><td data-label="Id">6</td></tr>
        <tr class="text-success"><td data-label="Id">7</td></tr>
        <tr class="text-success"><td data-label="Id">8</td></tr>
        <tr class="text-success"><td data-label="Id">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testShowFooter(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id']);
        $gridView = $gridView->dataProvider($dataProvider)->showFooter();

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tfoot>
        <tr><td>&nbsp;</td></tr>
        </tfoot>
        <tbody>
        <tr><td data-label="Id">1</td></tr>
        <tr><td data-label="Id">2</td></tr>
        <tr><td data-label="Id">3</td></tr>
        <tr><td data-label="Id">4</td></tr>
        <tr><td data-label="Id">5</td></tr>
        <tr><td data-label="Id">6</td></tr>
        <tr><td data-label="Id">7</td></tr>
        <tr><td data-label="Id">8</td></tr>
        <tr><td data-label="Id">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testSummaryOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = GridView::widget()
            ->dataProvider($dataProvider)
            ->Summary('Summary')
            ->SummaryOptions(['class' => 'text-danger']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        <tr></tr>
        </tbody></table>
        <div class="text-danger">Summary</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testShowOnEmpty(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData([]);

        $gridView = GridView::widget()->dataProvider($dataProvider)->showOnEmpty();

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="table">
        <thead>
        <tr></tr>
        </thead>
        <tbody>
        <tr><td colspan="0"><div class="empty">No results found.</div></td></tr>
        </tbody></table>

        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testTableOptions(): void
    {
        GridView::counter(0);

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id']);
        $gridView = $gridView->dataProvider($dataProvider)->tableOptions(['class' => 'text-success']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">

        <table class="text-success">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tbody>
        <tr><td data-label="Id">1</td></tr>
        <tr><td data-label="Id">2</td></tr>
        <tr><td data-label="Id">3</td></tr>
        <tr><td data-label="Id">4</td></tr>
        <tr><td data-label="Id">5</td></tr>
        <tr><td data-label="Id">6</td></tr>
        <tr><td data-label="Id">7</td></tr>
        <tr><td data-label="Id">8</td></tr>
        <tr><td data-label="Id">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }

    public function testToolbar(): void
    {
        GridView::counter(0);

        $itemsDropdown = [
            '1' => '1',
            '5' => '5',
            '10' => '10',
            '20' => '20',
            '25' => '25',
            '50' => '50',
        ];

        $toolbar = [
            [
                'content' =>
                    $this->html->tag('span', 'Page size:') . "\n" .
                    $this->html->dropDownList('pageSize', 5, $itemsDropdown, ['class' => 'ms-2']),
                'options' => ['class' => 'flex-fill float-start'],
            ],
            [
                'content' =>
                    $this->html->submitButton(
                        $this->html->tag('i', '', ['class' => 'bi bi-check-all']),
                        [
                            'class' => 'btn btn-success me-1',
                            'id' => 'button-send',
                            'title' => 'Apply changes',
                        ],
                    ) . "\n" .
                    $this->html->a(
                        $this->html->tag('i', '', ['class' => 'bi bi-bootstrap-reboot']),
                        $this->urlGenerator->generate('admin'),
                        [
                            'class' => 'btn btn-dark',
                            'id' => 'button-reset',
                            'title' => 'Reset grid',
                        ],
                    ),
                'options' => ['class' => 'float-end mb-2'],
            ],
        ];

        $dataProvider = new ArrayDataProvider();
        $dataProvider->allData($this->getArrayData());

        $gridView = $this->createGridView(['id']);
        $gridView = $gridView->dataProvider($dataProvider)->toolbar($toolbar)->toolbarOptions(['class' => 'toolbar']);

        $html = <<<'HTML'
        <div id="w1-gridview" class="grid-view">
        <div class="toolbar"><div class="flex-fill float-start"><span>Page size:</span>
        <select class="ms-2" name="pageSize">
        <option value="1">1</option>
        <option value="5" selected>5</option>
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="25">25</option>
        <option value="50">50</option>
        </select>
        </div><div class="float-end mb-2"><button type="submit" id="button-send" class="btn btn-success me-1" title="Apply changes"><i class="bi bi-check-all"></i></button>
        <a id="button-reset" class="btn btn-dark" href="/admin/index" title="Reset grid"><i class="bi bi-bootstrap-reboot"></i></a>
        </div></div>
        <table class="table">
        <thead>
        <tr><th>Id</th></tr>
        </thead>
        <tbody>
        <tr><td data-label="Id">1</td></tr>
        <tr><td data-label="Id">2</td></tr>
        <tr><td data-label="Id">3</td></tr>
        <tr><td data-label="Id">4</td></tr>
        <tr><td data-label="Id">5</td></tr>
        <tr><td data-label="Id">6</td></tr>
        <tr><td data-label="Id">7</td></tr>
        <tr><td data-label="Id">8</td></tr>
        <tr><td data-label="Id">9</td></tr>
        </tbody></table>
        <div class="summary">Showing <b>1-9</b> of <b>9</b> items</div>
        </div>
        HTML;
        $this->assertEqualsWithoutLE($html, $gridView->render());
    }
}
