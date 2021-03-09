<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests\Helper;

use Yii\Extension\GridView\Tests\TestCase;

final class SortTest extends TestCase
{
    public function testAttributeOrders()
    {
        $this->sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ]
        )->params(['sort' => 'age,-name'])->multiSort(true);

        $this->sort->attributeOrders(['age' => SORT_DESC, 'name' => SORT_ASC]);
        $this->assertEquals(['age' => SORT_DESC, 'name' => SORT_ASC], $this->sort->getAttributeOrders());

        $this->sort->multiSort(false);

        $this->sort->attributeOrders(['age' => SORT_DESC, 'name' => SORT_ASC]);
        $this->assertEquals(['age' => SORT_DESC], $this->sort->getAttributeOrders());

        $this->sort->attributeOrders(['age' => SORT_DESC, 'name' => SORT_ASC], false);
        $this->assertEquals(['age' => SORT_DESC, 'name' => SORT_ASC], $this->sort->getAttributeOrders());

        $this->sort->attributeOrders(['unexistingAttribute' => SORT_ASC]);
        $this->assertEquals([], $this->sort->getAttributeOrders());

        $this->sort->attributeOrders(['unexistingAttribute' => SORT_ASC], false);
        $this->assertEquals(['unexistingAttribute' => SORT_ASC], $this->sort->getAttributeOrders());
    }

    public function testGetOrders(): void
    {
        $this->sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ]
        )->params(['sort' => 'age,-name'])->multiSort();

        $orders = $this->sort->getOrders();

        $this->assertCount(3, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
        $this->assertEquals(SORT_DESC, $orders['first_name']);
        $this->assertEquals(SORT_DESC, $orders['last_name']);

        $this->sort->multiSort(false);

        $orders = $this->sort->getAttributeOrders(true);

        $this->assertCount(1, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
    }

    /**
     * @depends testGetOrders
     */
    public function testGetAttributeOrders()
    {
        $this->sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ],
        )->params(['sort' => 'age,-name'])->multiSort();

        $orders = $this->sort->getAttributeOrders();
        $this->assertCount(2, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
        $this->assertEquals(SORT_DESC, $orders['name']);

        $this->sort->multiSort(false);

        $orders = $this->sort->getAttributeOrders(true);
        $this->assertCount(1, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
    }

    /**
     * @depends testGetAttributeOrders
     */
    public function testGetAttributeOrder()
    {
        $this->sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ]
        )->params(['sort' => 'age,-name'])->multiSort();

        $this->assertEquals(SORT_ASC, $this->sort->getAttributeOrder('age'));
        $this->assertEquals(SORT_DESC, $this->sort->getAttributeOrder('name'));
        $this->assertNull($this->sort->getAttributeOrder('xyz'));
    }

    /**
     * @depends testGetOrders
     *
     * @see https://github.com/yiisoft/yii2/pull/13260
     */
    public function testGetExpressionOrders()
    {
        $this->sort->attributes(
            [
                'name' => [
                    'asc' => '[[last_name]] ASC NULLS FIRST',
                    'desc' => '[[last_name]] DESC NULLS LAST',
                ],
            ]
        );

        $this->sort->params(['sort' => '-name']);
        $orders = $this->sort->getOrders();
        $this->assertCount(1, $orders);
        $this->assertEquals('[[last_name]] DESC NULLS LAST', $orders[0]);

        $this->sort->params(['sort' => 'name']);
        $orders = $this->sort->getOrders(true);
        $this->assertCount(1, $orders);
        $this->assertEquals('[[last_name]] ASC NULLS FIRST', $orders[0]);
    }
}
