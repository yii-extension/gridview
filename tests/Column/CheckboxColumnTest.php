<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Column;

use Yii\Extension\GridView\Column\CheckboxColumn;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Tests\TestCase;

final class CheckboxColumnTest extends TestCase
{
    public function testCssClass(): void
    {
        $column = $this->checkboxColumn->cssClass('testMe')->grid($this->createGridView());
        $html = <<<'HTML'
        <td><input type="checkbox" class="testMe" name="selection[]" value="1"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 1, 0));
    }

    public function testContent(): void
    {
        $column = $this->checkboxColumn
            ->content(static fn ($model, $key, $index, $column) => '')
            ->grid($this->createGridView());
        $html = <<<'HTML'
        <td></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 1, 0));

        $column = $this->checkboxColumn
            ->content(fn ($model, $key, $index, $column) => $this->html->checkBox('checkBoxInput', false))
            ->grid($this->createGridView());
        $this->assertSame(
            '<td>' . $this->html->checkBox('checkBoxInput', false) . '</td>',
            $column->renderDataCell([], 1, 0)
        );
    }

    public function testName(): void
    {
        $column = $this->checkboxColumn->name('selection')->grid($this->createGridView());
        $html = <<<'HTML'
        <th><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());

        $column = $this->checkboxColumn->name('selections[]')->grid($this->createGridView());
        $html = <<<'HTML'
        <th><input type="checkbox" class="select-on-check-all" name="selections_all" value="1"></th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());

        $column = $this->checkboxColumn->name('MyForm[grid1]')->grid($this->createGridView());
        $html = <<<'HTML'
        <th><input type="checkbox" class="select-on-check-all" name="MyForm[grid1_all]" value="1"></th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());

        $column = $this->checkboxColumn->name('MyForm[grid1][]')->grid($this->createGridView());
        $html = <<<'HTML'
        <th><input type="checkbox" class="select-on-check-all" name="MyForm[grid1_all]" value="1"></th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());

        $column = $this->checkboxColumn->name('MyForm[grid1][key]')->grid($this->createGridView());
        $html = <<<'HTML'
        <th><input type="checkbox" class="select-on-check-all" name="MyForm[grid1][key_all]" value="1"></th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());

        $column = $this->checkboxColumn->name('MyForm[grid1][key][]')->grid($this->createGridView());
        $html = <<<'HTML'
        <th><input type="checkbox" class="select-on-check-all" name="MyForm[grid1][key_all]" value="1"></th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());
    }

    public function testNameEmpty(): void
    {
        $this->expectException(InvalidConfigException::class);
        $column = $this->checkboxColumn->name('')->grid($this->createGridView());
    }

    public function testNotMultiple(): void
    {
        $column = $this->checkboxColumn->notMultiple()->grid($this->createGridView());
        $html = <<<'HTML'
        <td><input type="checkbox" name="selection[]" value="1"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 1, 0));
    }

    public function testRenderHeader(): void
    {
        $column = $this->checkboxColumn->grid($this->createGridView());
        $html = <<<'HTML'
        <th><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());

        $column = $this->checkboxColumn->grid($this->createGridView())->notMultiple();
        $html = <<<'HTML'
        <th>&nbsp;</th>
        HTML;
        $this->assertSame($html, $column->renderHeaderCell());

    }

    public function testValue(): void
    {
        $column = $this->checkboxColumn->grid($this->createGridView());
        $html = <<<'HTML'
        <td><input type="checkbox" name="selection[]" value="1"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 1, 0));

        $html = <<<'HTML'
        <td><input type="checkbox" name="selection[]" value="42"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 42, 0));

        $html = <<<'HTML'
        <td><input type="checkbox" name="selection[]" value="[1,42]"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], [1,42], 0));

        $column = $this->checkboxColumn->checkboxOptions(['value' => 42])->grid($this->createGridView());
        $this->assertStringNotContainsString('value="1"', $column->renderDataCell([], 1, 0));

        $html = <<<'HTML'
        <td><input type="checkbox" name="selection[]" value="42"></td>
        HTML;
        $this->assertSame($html, $column->renderDataCell([], 1, 0));
    }
}
