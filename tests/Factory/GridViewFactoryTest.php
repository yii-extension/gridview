<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Factory;

use stdClass;
use RuntimeException;
use Yii\Extension\GridView\Column\ActionColumn;
use Yii\Extension\GridView\Column\CheckboxColumn;
use Yii\Extension\GridView\Column\Column;
use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\Column\RadioButtonColumn;
use Yii\Extension\GridView\Column\SerialColumn;
use Yii\Extension\GridView\Tests\TestCase;

final class GridViewFactoryTest extends TestCase
{
    public function testCreateActionColumnClass(): void
    {
        $config = ['class' => ActionColumn::class];

        $column = $this->gridViewFactory->createColumnClass($config);
        $this->assertInstanceOf(Column::class, $column);
    }

    public function testCreateCheckboxClass(): void
    {
        $config = ['class' => CheckboxColumn::class];

        $column = $this->gridViewFactory->createColumnClass($config);
        $this->assertInstanceOf(Column::class, $column);
    }

    public function testCreateDataColumnClass(): void
    {
        $config = ['class' => DataColumn::class];

        $column = $this->gridViewFactory->createColumnClass($config);
        $this->assertInstanceOf(Column::class, $column);
    }

    public function testCreateRadioButtonColumnClass(): void
    {
        $config = ['class' => RadioButtonColumn::class];

        $column = $this->gridViewFactory->createColumnClass($config);
        $this->assertInstanceOf(Column::class, $column);
    }

    public function testCreateSerialColumnClass(): void
    {
        $config = ['class' => SerialColumn::class];

        $column = $this->gridViewFactory->createColumnClass($config);
        $this->assertInstanceOf(Column::class, $column);
    }

    public function testException(): void
    {
        $config = ['class' => stdClass::class];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "stdClass" is not an instance of the "Yii\Extension\GridView\Column\Column".'
        );
        $column = $this->gridViewFactory->createColumnClass($config);
    }
}
