<?php

declare(strict_types=1);

namespace Yii\Extension\GridView;

use Closure;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Html;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Strings\Inflector;

/**
 * DetailView displays the detail of a single data model.
 *
 * DetailView is best used for displaying a model in a regular format (e.g. each model attribute is displayed as a row
 * in a table.) The model can be either an instance of FormModelInterface, ActiveRecordInterface or an associative
 * array.
 *
 * DetailView uses the {@see attributes} property to determines which model attributes should be displayed and how they
 * should be formatted.
 *
 * A typical usage of DetailView is as follows:
 *
 * ```php
 * echo DetailView::widget()
 *     ->data($data)
 *     ->attributes(
 *         [
 *             'title',            // title attribute (in plain text)
 *             'description:html', // description attribute in HTML
 *             [
 *                 'label' => 'Owner',
 *                 'value' => $data->owner->name,
 *             ],
 *             'created_at:datetime', // creation date formatted as datetime
 *         ],
 *     );
 * ```
 *
 * For more details and usage information on DetailView,
 * see the [guide article on data widgets](guide:output-data-widgets).
 */
final class DetailView extends Widget
{
    private array $attributes = [];
    private array $captionOptions = [];
    private array $contentOptions = [];
    /** @var array|object|null */
    private $data = null;
    public Html $html;
    private Inflector $inflector;
    public array $options = ['class' => 'table'];
    private array $rowOptions = [];
    public string $template = '<tr{rowOptions}><th{captionOptions}>{label}</th><td{contentOptions}>{value}</td></tr>';

    public function __construct(Inflector $inflector, Html $html)
    {
        $this->inflector = $inflector;
        $this->html = $html;
    }

    /**
     * Renders the detail view.
     * This is the main entry of the whole detail view rendering.
     */
    protected function run(): string
    {
        $attributesNormalize = $this->normalizeAttributes();

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        $rows = [];

        /** @var array<array-key,mixed> */
        foreach ($attributesNormalize as $attribute) {
            $rows[] = $this->renderAttribute($attribute);
        }

        $options = $this->options;

        /** @var string */
        $tag = $options['tag'] ?? 'table';

        unset($options['tag']);

        return $this->html->tag($tag, "\n" . implode("\n", $rows) . "\n", $options);
    }

    /**
     * @param array $attributes a list of attributes to be displayed in the detail view.
     *
     * Each array element represents the specification for displaying one particular attribute.
     *
     * An attribute can be specified as a string in the format of `attribute`, `attribute:format` or
     * `attribute:format:label`, where `attribute` refers to the attribute name, and `format` represents the format of
     * the attribute.
     *
     * An attribute can also be specified in terms of an array with the following elements:
     *
     * - `attribute`: the attribute name. This is required if either `label` or `value` is not specified.
     * - `label`: the label associated with the attribute. If this is not specified, it will be generated from the
     *    attribute name.
     * - `value`: the value to be displayed. If this is not specified, it will be retrieved from {@see data} using the
     *    attribute name by calling {@see ArrayHelper::getValue()}.
     * - Note that this value will be formatted into a displayable text according to the `format` option.
     *   parameters:
     *
     * ```php
     *     function ($data, $widget)
     * ```
     *
     * `$data` refers to displayed model and `$widget` is an instance of `DetailView` widget.
     *
     * - `format`: the type of the value that determines how the value would be formatted into a displayable text.
     * - `visible`: whether the attribute is visible. If set to `false`, the attribute will NOT be displayed.
     *
     * @psalm-param array<array-key,array|string|Closure> $attributes
     *
     * @return $this
     */
    public function attributes(array $attributes): self
    {
        $new = clone $this;
        $new->attributes = $attributes;

        return $new;
    }

    /**
     * @param array $captionOptions the `HTML` attributes for customize all labels tag.
     *
     * The `tag` option specifies what container tag should be used. It defaults to `table` if not set.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return $this
     */
    public function captionOptions(array $captionOptions): self
    {
        $new = clone $this;
        $new->captionOptions = $captionOptions;

        return $new;
    }

    /**
     * @param array $contentOptions the `HTML` attributes for customize all values tag.
     *
     * The `tag` option specifies what container tag should be used. It defaults to `table` if not set.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return $this
     */
    public function contentOptions(array $contentOptions): self
    {
        $new = clone $this;
        $new->contentOptions = $contentOptions;

        return $new;
    }

    /**
     * @param array|object $data the data model whose details are to be displayed. This can be a {@see data} instance,
     * an associative array, an object that implements {@see ArrayableInterface} interface.
     *
     * @return $this
     */
    public function data($data): self
    {
        $new = clone $this;
        $new->data = $data;

        return $new;
    }

    /**
     * @param array $options the `HTML` attributes for the container tag of this widget. The `tag` option specifies what
     * container tag should be used. It defaults to `table` if not set.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function options(array $options): self
    {
        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * @param array $rowOptions the `HTML` attributes for customize all rows data.
     *
     * The `tag` option specifies what container tag should be used. It defaults to `table` if not set.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return $this
     */
    public function rowOptions(array $rowOptions): self
    {
        $new = clone $this;
        $new->rowOptions = $rowOptions;

        return $new;
    }

    /**
     * @param string $template the template used to render a single attribute.
     *
     * If a string, the token `{label}`  and `{value}` will be replaced with the label and the value of the
     * corresponding attribute.
     *
     * where `$attribute` refer to the specification of the attribute being rendered, `$index` is the zero-based
     * index of the attribute in the {@see attributes} array, and `$widget` refers to this widget instance.
     *
     * @return $this
     */
    public function template(string $template): self
    {
        $new = clone $this;
        $new->template = $template;

        return $new;
    }

    /**
     * Normalizes the attribute specifications.
     *
     * @return array
     */
    private function normalizeAttributes(): array
    {
        $attributes = $this->attributes;

        /** @var array<array-key,array|string|Closure> $attributes */
        foreach ($attributes as $i => $attribute) {
            if (is_string($attribute)) {
                if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $attribute, $matches)) {
                    throw new InvalidConfigException(
                        'The attribute must be specified in the format of "attribute", "attribute:format" or '
                        . '"attribute:format:label" '
                    );
                }

                $attribute = [
                    'attribute' => $matches[1],
                    'format' => $matches[3] ?? 'text',
                    'label' => $matches[5]  ?? null,
                ];
            }

            if (is_array($attribute)) {
                if (isset($attribute['visible']) && !$attribute['visible']) {
                    unset($attributes[$i]);
                    continue;
                }

                if (!isset($attribute['format'])) {
                    $attribute['format'] = 'text';
                }

                if (isset($attribute['attribute'])) {
                    /** @var string */
                    $attributeName = $attribute['attribute'];
                    if (!isset($attribute['label'])) {
                        $attribute['label'] = $this->inflector->toHumanReadable($attributeName, true);
                    }

                    if (!array_key_exists('value', $attribute) && $this->data !== null) {
                        /** @var mixed */
                        $attribute['value'] = ArrayHelper::getValue($this->data, $attributeName);
                    }
                } elseif (!isset($attribute['label']) || !array_key_exists('value', $attribute)) {
                    throw new InvalidConfigException('The attribute configuration requires the "attribute" element to determine the value and display label.');
                }

                if ($attribute['value'] instanceof Closure) {
                    /** @var mixed */
                    $attribute['value'] = call_user_func($attribute['value'], $this->data, $this);
                }
            }

            $attributes[$i] = $attribute;
        }

        return $attributes;
    }

    /**
     * Renders a single attribute.
     *
     * @param array $attribute the specification of the attribute to be rendered.
     *
     * @return string the rendering result.
     */
    private function renderAttribute(array $attribute): string
    {
        /** @var array */
        $captionOptions = $attribute['captionOptions'] ?? [];
        $captionOptions = array_merge_recursive($this->captionOptions, $captionOptions);

        /** @var array */
        $contentOptions = $attribute['contentOptions'] ?? [];
        $contentOptions = array_merge_recursive($this->contentOptions, $contentOptions);

        /** @var array */
        $rowOptions = $attribute['rowOptions'] ?? [];
        $rowOptions = array_merge_recursive($this->rowOptions, $rowOptions);

        return strtr($this->template, [
            '{label}' => $attribute['label'],
            '{value}' => $attribute['value'],
            '{captionOptions}' => $this->html->renderTagAttributes($captionOptions),
            '{contentOptions}' => $this->html->renderTagAttributes($contentOptions),
            '{rowOptions}' => $this->html->renderTagAttributes($rowOptions),
        ]);
    }
}
