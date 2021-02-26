<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Column;

use JsonException;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Html;
use Yiisoft\Json\Json;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * CheckboxColumn displays a column of checkboxes in a grid view.
 *
 * To add a CheckboxColumn to the {@see GridView}, add it to the {@see GridView::columns|columns} configuration as
 * follows:
 *
 * ```php
 * [
 *     '__class' => 'yii\grid\CheckboxColumn',
 *     // you may configure additional properties here
 * ],
 * ```
 *
 * For more details and usage information on CheckboxColumn.
 *
 * {@see the [guide article on data widgets](guide:output-data-widgets)}.
 */
final class CheckboxColumn extends Column
{
    private string $cssClass = '';
    private array $checkboxOptions = [];
    private bool $multiple = true;
    private string $name = 'selection';

    public function __construct(Html $html, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($html, $urlGenerator);

        $this->name = $this->html->getArrayableName($this->name);

        $this->registerClientScript();
    }

    /**
     * @param string the css class that will be used to find the checkboxes.
     *
     * @return $this
     */
    public function cssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    /**
     * Set the HTML attributes for checkboxes.
     *
     * @param array $checkboxOptions the HTML attributes for checkboxes.
     *
     * @return $this
     */
    public function checkboxOptions(array $checkboxOptions): self
    {
        $this->checkboxOptions = $checkboxOptions;

        return $this;
    }

    /**
     * @param string the name of the input checkbox input fields. This will be appended with `[]` to ensure it is an
     * array.
     *
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;

        if ($this->name === '') {
            throw new InvalidConfigException('The "name" property must be set.');
        }

        return $this;
    }

    /**
     * @param bool whether it is possible to select multiple rows. Defaults to `true`.
     *
     * @return $this
     */
    public function notMultiple(): self
    {
        $this->multiple = false;

        return $this;
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
    protected function renderDataCellContent($arClass, $key, int $index): string
    {
        if (!empty($this->content)) {
            return parent::renderDataCellContent($arClass, $key, $index);
        }

        $options = $this->checkboxOptions;

        if (!isset($options['value'])) {
            /** @var mixed */
            $options['value'] = is_array($key) ? Json::encode($key) : $key;
        }

        if ($this->cssClass !== '') {
            $this->html->addCssClass($options, $this->cssClass);
        }

        return $this->html->checkbox($this->name, !empty($options['checked']), $options);
    }

    /**
     * Renders the header cell content.
     *
     * The default implementation simply renders {@see header}.
     * This method may be overridden to customize the rendering of the header cell.
     *
     * @throws JsonException
     *
     * @return string the rendering result
     */
    protected function renderHeaderCellContent(): string
    {
        if ($this->label !== '' || $this->multiple === false) {
            return parent::renderHeaderCellContent();
        }

        return $this->html->checkbox($this->getHeaderCheckBoxName(), false, ['class' => 'select-on-check-all']);
    }

    /**
     * Returns header checkbox name.
     *
     * @return string header checkbox name.
     */
    protected function getHeaderCheckBoxName(): string
    {
        $name = $this->name;

        if (substr_compare($name, '[]', -2, 2) === 0) {
            $name = substr($name, 0, -2);
        }
        if (substr_compare($name, ']', -1, 1) === 0) {
            $name = substr($name, 0, -1) . '_all]';
        } else {
            $name .= '_all';
        }

        return $name;
    }

    /**
     * Registers the needed JavaScript.
     */
    private function registerClientScript(): void
    {
    }
}
