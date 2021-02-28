<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Column;

use Yii\Extension\GridView\DetailView;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Tests\TestCase;

final class DetailViewTest extends TestCase
{
    public function testAttributes(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr><th>Id</th><td>1</td></tr>
        <tr><th>Username</th><td>tests 1</td></tr>
        <tr><th>Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username'],
                    ['attribute' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testAttributesException(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'The attribute configuration requires the "attribute" element to determine the value and display label.'
        );
        $detailView = DetailView::widget()
            ->attributes([['label' => 'id']])
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->render();
    }

    public function testAttributesFormatException(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'The attribute must be specified in the format of "attribute", "attribute:format" or ' .
            '"attribute:format:label"'
        );
        $detailView = DetailView::widget()
            ->attributes([''])
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->render();
        die;
    }

    public function testAttributesFormatString(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr><th>Id</th><td>1</td></tr>
        <tr><th>Username</th><td>tests 1</td></tr>
        <tr><th>Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(['id', 'username', 'total'])
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testCaptionOptions(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr><th class="text-success">Id</th><td>1</td></tr>
        <tr><th class="text-success">Username</th><td>tests 1</td></tr>
        <tr><th class="text-success">Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username'],
                    ['attribute' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->captionOptions(['class' => 'text-success']);
        $this->assertEqualsWithoutLE($html, $detailView->render());

        $html = <<<'HTML'
        <table id="w2" class="table">
        <tr><th class="text-success">Id</th><td>1</td></tr>
        <tr><th class="text-success">Username</th><td>tests 1</td></tr>
        <tr><th class="text-success" style="width:20px;">Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username'],
                    ['attribute' => 'total', 'captionOptions' => ['style' => 'width:20px;']],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->captionOptions(['class' => 'text-success']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testContentOptions(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr><th>Id</th><td class="text-success">1</td></tr>
        <tr><th>Username</th><td class="text-success">tests 1</td></tr>
        <tr><th>Total</th><td class="text-success">10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username'],
                    ['attribute' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->contentOptions(['class' => 'text-success']);
        $this->assertEqualsWithoutLE($html, $detailView->render());

        $html = <<<'HTML'
        <table id="w2" class="table">
        <tr><th>Id</th><td class="text-success">1</td></tr>
        <tr><th>Username</th><td class="text-success">tests 1</td></tr>
        <tr><th>Total</th><td class="text-success" style="width:20px;">10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username'],
                    ['attribute' => 'total', 'contentOptions' => ['style' => 'width:20px;']],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->contentOptions(['class' => 'text-success']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testLabel(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr><th>id</th><td>1</td></tr>
        <tr><th>username</th><td>tests 1</td></tr>
        <tr><th>total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id', 'label' => 'id'],
                    ['attribute' => 'username', 'label' => 'username'],
                    ['attribute' => 'total', 'label' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testOptions(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table table-hoverable">
        <tr><th>Id</th><td>1</td></tr>
        <tr><th>Username</th><td>tests 1</td></tr>
        <tr><th>Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username',],
                    ['attribute' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->options(['class' => 'table table-hoverable']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testRender(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">

        </table>
        HTML;

        $detailView = DetailView::widget();
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testRowOptions(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr class="alert alert-sucess"><th>Id</th><td>1</td></tr>
        <tr class="alert alert-sucess"><th>Username</th><td>tests 1</td></tr>
        <tr class="alert alert-sucess"><th>Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username'],
                    ['attribute' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->rowOptions(['class' => 'alert alert-sucess']);
        $this->assertEqualsWithoutLE($html, $detailView->render());

        $html = <<<'HTML'
        <table id="w2" class="table">
        <tr class="alert alert-sucess"><th>Id</th><td>1</td></tr>
        <tr class="alert alert-sucess"><th>Username</th><td>tests 1</td></tr>
        <tr class="alert alert-sucess text-center"><th>Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username'],
                    ['attribute' => 'total', 'rowOptions' => ['class' => 'text-center']],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->rowOptions(['class' => 'alert alert-sucess']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testTemplate(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr class="text-center"><th>Id</th><td>1</td></tr>
        <tr class="text-center"><th>Username</th><td>tests 1</td></tr>
        <tr class="text-center"><th>Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username',],
                    ['attribute' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10'])
            ->template(
                '<tr class="text-center"><th{captionOptions}>{label}</th><td{contentOptions}>{value}</td></tr>'
            );
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testValue(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr><th>Id</th><td>1</td></tr>
        <tr><th>Username</th><td>testMe</td></tr>
        <tr><th>Total</th><td>200</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username', 'value' => 'testMe'],
                    ['attribute' => 'total', 'value' => static fn() => 10 * 20],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }

    public function testVisible(): void
    {
        DetailView::counter(0);

        $html = <<<'HTML'
        <table id="w1" class="table">
        <tr><th>Id</th><td>1</td></tr>
        <tr><th>Total</th><td>10</td></tr>
        </table>
        HTML;

        $detailView = DetailView::widget()
            ->attributes(
                [
                    ['attribute' => 'id'],
                    ['attribute' => 'username', 'visible' => false],
                    ['attribute' => 'total'],
                ],
            )
            ->data(['id' => 1, 'username' => 'tests 1', 'total' => '10']);
        $this->assertEqualsWithoutLE($html, $detailView->render());
    }
}
