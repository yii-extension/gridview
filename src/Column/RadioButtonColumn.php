<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use Closure;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Html;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * RadioButtonColumn displays a column of radio buttons in a grid view.
 *
 * To add a RadioButtonColumn to the {@see GridView}, add it to the {@see GridView::columns|columns} configuration as
 * follows:
 *
 * ```php
 * [
 *     '__class' => 'yii\grid\RadioButtonColumn',
 *     'label()' => ['#'],
 *     'radioOptions()' => [['class' => 'testMe']],
 * ]
 * ```
 */
final class RadioButtonColumn extends Column
{
    private string $name = 'radioButtonSelection';
    private array $radioOptions = [];

    /**
     * @throws InvalidConfigException if {@see name} is not set.
     */
    public function __construct(Html $html, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($html, $urlGenerator);

        $this->html = $html;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Renders the data cell content.
     *
     * @param array|object $arClass the data arClass.
     * @param mixed $key the key associated with the data arClass.
     * @param int $index the zero-based index of the data arClass among the arClasss array returned by
     * {@see GridView::dataProvider}.
     *
     * @return string the rendering result.
     */
    protected function renderDataCellContent($arClass, $key, $index): string
    {
        if (!empty($this->content)) {
            return parent::renderDataCellContent($arClass, $key, $index);
        }

        $options = $this->radioOptions;

        if (!isset($options['value'])) {
            /** @var mixed */
            $options['value'] = is_array($key)
                ? json_encode($key, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : $key;
        }

        /** @var bool */
        $checked = $options['checked'] ?? false;

        return $this->html->radio($this->name, $checked, $options);
    }

    /**
     * @param string $name the name of the input radio button input fields.
     *
     * @return $this
     */
    public function name(string $name): self
    {
        if (empty($name)) {
            throw new InvalidConfigException('The "name" property it cannot be empty.');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @param array $radioOptions the HTML attributes for the radio buttons.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function radioOptions(array $radioOptions): self
    {
        $this->radioOptions = $radioOptions;

        return $this;
    }
}
