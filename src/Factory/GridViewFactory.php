<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Factory;

use RuntimeException;
use Yii\Extension\GridView\Column\Column;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Factory\Factory;

final class GridViewFactory extends Factory
{
    /**
     * Creates a DataColumn defined by config passed
     *
     * @param array $config parameters for creating a widget
     *
     * @throws RuntimeException if factory was not initialized
     * @throws InvalidConfigException
     *
     * @psalm-suppress MoreSpecificReturnType
     *
     * @return Column
     */
    public function createColumnClass(array $config): Column
    {
        $class = $this->create($config);

        if (!($class instanceof Column)) {
            throw new RuntimeException(sprintf('The "%s" is not an instance of the "%s".', $class, Column::class));
        }

        return $class;
    }
}
