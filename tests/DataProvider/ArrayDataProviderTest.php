<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\DataProvider;

use Yii\Extension\GridView\DataProvider\ArrayDataProvider;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Tests\TestCase;

final class ArrayDataProviderTest extends TestCase
{
    public function testKey(): void
    {
        $simpleArray = [
            ['name' => 'zero'],
            ['name' => 'one'],
            ['name' => 'tow'],
        ];
        $dataProvider = new ArrayDataProvider();
        $dataProvider = $dataProvider->allData($simpleArray)->key('name');

        $dataProvider->getPagination()->pageSize(2);
        $this->assertEquals(['zero', 'one'], $dataProvider->getKeys());

        $nestedArray = [
            ['name' => ['first' => 'joe', 'last' => 'dow']],
            ['name' => ['first' => 'nikita', 'last' => 'femme']],
            ['name' => 'tow'],
        ];
        $dataProvider = new ArrayDataProvider();
        $dataProvider = $dataProvider->allData($nestedArray)->key(static fn($arClass) => $arClass['name']['first']);

        $dataProvider->getPagination()->pageSize(2);
        $this->assertEquals(['joe', 'nikita'], $dataProvider->getKeys());
    }

    public function testKeyException(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The property "key" must be of type "string" or "callable".');
        $dataProvider = new ArrayDataProvider();
        $dataProvider = $dataProvider->key(['name']);
    }

    public function testGetKeys(): void
    {
        $simpleArray = [
            ['name' => 'zero'],
            ['name' => 'one'],
            ['name' => 'tow'],
        ];
        $dataProvider = new ArrayDataProvider();
        $dataProvider = $dataProvider->allData($simpleArray);

        $dataProvider->getPagination()->pageSize(2);
        $this->assertEquals([0, 1], $dataProvider->getKeys());

        $namedArray = [
            'key1' => ['name' => 'zero'],
            'key2' => ['name' => 'one'],
            'key3' => ['name' => 'two'],
        ];
        $dataProvider = new ArrayDataProvider();
        $dataProvider = $dataProvider->allData($namedArray);

        $dataProvider->getPagination()->pageSize(2);
        $this->assertEquals(['key1', 'key2'], $dataProvider->getKeys());

        $mixedArray = [
            'key1' => ['name' => 'zero'],
            9 => ['name' => 'one'],
            'key3' => ['name' => 'two'],
        ];
        $dataProvider = new ArrayDataProvider();
        $dataProvider = $dataProvider->allData($mixedArray);

        $dataProvider->getPagination()->pageSize(2);
        $this->assertEquals(['key1', 9], $dataProvider->getKeys());
    }

    public function testGetARClasses(): void
    {
        $simpleArray = [
            ['name' => 'zero'],
            ['name' => 'one'],
        ];

        $dataProvider = new ArrayDataProvider();
        $dataProvider = $dataProvider->allData($simpleArray);
        $this->assertEquals($simpleArray, $dataProvider->getARClasses());
    }
}
