<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Helper;

use InvalidArgumentException;
use JsonException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Json\Json;

final class Html
{
    /**
     * @var array the preferred order of attributes in a tag. This mainly affects the order of the attributes that are
     * rendered by {@see renderTagAttributes()}.
     *
     * @psalm-var array<array-key, string>
     */
    private array $attributeOrder = [
        'type',
        'id',
        'class',
        'name',
        'value',

        'href',
        'src',
        'srcset',
        'form',
        'action',
        'method',

        'selected',
        'checked',
        'readonly',
        'disabled',
        'multiple',

        'size',
        'maxlength',
        'width',
        'height',
        'rows',
        'cols',

        'alt',
        'title',
        'rel',
        'media',
    ];

    /**
     * @var array list of tag attributes that should be specially handled when their values are of array type.
     *
     * In particular, if the value of the `data` attribute is `['name' => 'xyz', 'age' => 13]`, two attributes will be
     * generated instead of one: `data-name="xyz" data-age="13"`.
     */
    private array $dataAttributes = ['aria', 'data', 'data-ng', 'ng'];

    /** @var array<array-key, string> */
    private array $voidElement = [
        'area' => '',
        'base' => '',
        'br' => '',
        'col' => '',
        'command' => '',
        'embed' => '',
        'hr' => '',
        'img' => '',
        'input' => '',
        'keygen' => '',
        'link' => '',
        'meta' => '',
        'param' => '',
        'source' => '',
        'track' => '',
        'wbr' => '',
    ];

    /**
     * Generates a hyperlink tag.
     *
     * @param string $text Link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code such as an image
     * tag. If this is coming from end users, you should consider {@see encode()} it to prevent XSS attacks.
     * @param string|null $url The URL for the hyperlink tag. This parameter will be processed and will be used for the
     * "href" attribute of the tag. If this parameter is null, the "href" attribute will not be generated.
     *
     * If you want to use an absolute url you can call yourself, before passing the URL to this method, like this:
     *
     * ```php
     * Html::a('link text', $url, true))
     * ```
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as the attributes of
     * the resulting tag. The values will be HTML-encoded using {@see encode()}. If a value is null, the corresponding
     * attribute will not be rendered.
     *
     * See {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @throws JsonException
     *
     * @return string The generated hyperlink.
     */
    public function a(string $text, string $url = null, array $options = []): string
    {
        if ($url !== null) {
            $options['href'] = $url;
        }

        return $this->tag('a', $text, $options);
    }

    /**
     * Adds a CSS class (or several classes) to the specified options.
     *
     * If the CSS class is already in the options, it will not be added again. If class specification at given options
     * is an array, and some class placed there with the named (string) key, overriding of such key will have no
     * effect. For example:
     *
     * ```php
     * $options = ['class' => ['persistent' => 'initial']];
     *
     * // ['class' => ['persistent' => 'initial']];
     * Html::addCssClass($options, ['persistent' => 'override']);
     * ```
     *
     * @see removeCssClass()
     *
     * @param array $options The options to be modified.
     * @param string|string[] $class The CSS class(es) to be added.
     */
    public function addCssClass(array &$options, $class): void
    {
        if (isset($options['class'])) {
            /** @var string|string[] */
            $classOptions = $options['class'];

            if (is_array($classOptions)) {
                $options['class'] = $this->mergeCssClasses($classOptions, (array)$class);
            } else {
                $classes = preg_split('/\s+/', $classOptions, -1, PREG_SPLIT_NO_EMPTY);
                $options['class'] = implode(' ', $this->mergeCssClasses($classes, (array)$class));
            }
        } else {
            $options['class'] = $class;
        }
    }

    public function beginTag(string $name = '', array $options = []): string
    {
        if ($name === '') {
            return '';
        }

        $name = strtolower($name);

        return "<$name" . $this->renderTagAttributes($options) . '>';
    }

    /**
     * Generates a button tag.
     *
     * @param string $content The content enclosed within the button tag. It will NOT be HTML-encoded. Therefore you
     * can pass in HTML code such as an image tag. If this is is coming from end users, you should consider
     * {@see encode()} it to prevent XSS attacks.
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as the attributes
     * the resulting tag. The values will be HTML-encoded using {@see encode()}. If a value is null, the corresponding
     * attribute will not be rendered.
     * {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @throws JsonException
     *
     * @return string The generated button tag.
     */
    public function button(string $content = 'Button', array $options = []): string
    {
        if (!isset($options['type'])) {
            $options['type'] = 'button';
        }

        return $this->tag('button', $content, $options);
    }

    /**
     * Generates a checkbox input.
     *
     * @param string $name The name attribute.
     * @param bool $checked Whether the checkbox should be checked.
     * @param array $options The tag options in terms of name-value pairs.
     *
     * @see booleanInput()} for details about accepted attributes.
     *
     * @throws JsonException
     *
     * @return string The generated checkbox tag.
     */
    public function checkbox(string $name, bool $checked = false, array $options = []): string
    {
        return $this->booleanInput('checkbox', $name, $checked, $options);
    }

    /**
     * Decodes special HTML entities back to the corresponding characters.
     *
     * This is the opposite of {@see encode()}.
     *
     * @param string $content the content to be decoded
     *
     * @see encode()
     * @see https://secure.php.net/manual/en/function.htmlspecialchars-decode.php
     *
     * @return string the decoded content
     */
    public function decode(string $content): string
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }

    /**
     * Generates a drop-down list.
     *
     * @param string $name the input name.
     * @param array|string|null $selection the selected value(s). String for single or array for multiple selection(s).
     * @param array $items the option data items. The array keys are option values, and the array values are the
     * corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *
     * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *
     * If you have a list of data models, you may convert them into the format described above using
     * {@see ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in the labels
     * will also be HTML-encoded.
     * @param array $attributes the tag $attributes in terms of name-value pairs. The following $attributes are
     * specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option.
     *
     * ```php
     * [
     *     'text' => 'Please select',
     *     '$options' => ['value' => 'none', 'class' => 'prompt', 'label' => 'Select'],
     * ],
     * ```
     *
     * - $attributes: array, the attributes for the select option tags. The array keys must be valid option values, and
     *   the array values are the extra attributes for the corresponding option tags.
     *
     * For example,
     *
     * ```php
     * [
     *     'value1' => ['disabled' => true],
     *     'value2' => ['label' => 'value 2'],
     * ];
     * ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of '$attributes',
     *   except that the array keys represent the optgroup labels specified in $items.
     *
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to false.
     *
     * - encode: bool, whether to encode option prompt and option value characters. Defaults to `true`.
     *
     * - strict: boolean, if `$selection` is an array and this value is true a strict comparison will be performed on
     *   `$items` keys. Defaults to false.
     *
     * The rest of the $attributes will be rendered as the attributes of the resulting tag. The values will be
     * HTML-encoded using {@see encode()}. If a value is null, the corresponding attribute will not be rendered.
     *
     * See {@see Html::renderTagAttributes()} details on how attributes are being rendered.
     *
     * List of supported attributes: `autofocus`, `class`, `disabled`, `encode`, `encodeSpaces`, `form`, `groups`
     * `id`, `multiple`, `name`, `options`, `prompt`, `size`, `strict`, `unselect`.
     *
     * List of supported for prompt: `class`, `id`, `text`, `options`, `value`.
     *
     * see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/select.
     *
     * @return string the generated drop-down list tag
     */
    public function dropdownList(string $name, $selection = null, array $items = [], array $attributes = []): string
    {
        if (!empty($attributes['multiple'])) {
            return $this->listBox($name, $selection, $items, $attributes);
        }

        $attributes['name'] = $name;
        unset($attributes['unselect']);

        /**
         * @var string $selectContent
         * @var array $attributes
         */
        [$selectContent, $attributes] = $this->renderSelectOptions($selection, $items, $attributes);

        $html = $this->tag('select', "\n" . $selectContent . "\n", $attributes);

        if (empty($selectContent)) {
            $html = $this->tag('select', $selectContent, $attributes);
        }

        return $html;
    }

    /**
     * Encodes special characters into HTML entities.
     *
     * @param string $content the content to be encoded.
     * @param bool $doubleEncode whether to encode HTML entities in `$content`.
     * @param string $charset whether to encode HTML entities in `$content`.
     *
     * @return string the encoded content.
     *
     * {@see decode()}
     *
     * {@see https://secure.php.net/manual/en/function.htmlspecialchars.php}
     */
    public function encode(string $content, bool $doubleEncode = true, string $charset = 'UTF-8'): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, $charset, $doubleEncode);
    }

    public function endTag(string $name): string
    {
        if ($name === '') {
            return '';
        }

        return "</$name>";
    }

    /**
     * Returns the real attribute name from the given attribute expression.
     *
     * If `$attribute` has neither prefix nor suffix, it will be returned back without change.
     *
     * @param string $attribute the attribute name or expression
     *
     * @throws InvalidArgumentException if the attribute name contains non-word characters.
     *
     * @return string the attribute name without prefix and suffix.
     *
     * @see parseAttribute()
     */
    public function getAttributeName(string $attribute): string
    {
        return $this->parseAttribute($attribute)['name'];
    }

    public function getArrayableName(string $name): string
    {
        return substr($name, -2) !== '[]' ? $name . '[]' : $name;
    }

    /**
     * Generates an appropriate input name for the specified attribute name or expression.
     *
     * This method generates a name that can be used as the input name to collect user input for the specified
     * attribute. The name is generated according to the of the form and the given attribute name. For example, if the
     * form name of the `Post` form is `Post`, then the input name generated for the `content` attribute would be
     * `Post[content]`.
     *
     * See {@see getAttributeName()} for explanation of attribute expression.
     *
     * @param string $formName the form name.
     * @param string $attribute the attribute name or expression.
     *
     * @throws InvalidArgumentException if the attribute name contains non-word characters
     * or empty form name for tabular inputs
     *
     * @return string the generated input name.
     */
    public function getInputName(string $formName, string $attribute): string
    {
        $data = self::parseAttribute($attribute);

        if ($formName === '' && $data['prefix'] === '') {
            return $attribute;
        }

        if ($formName !== '') {
            return $formName . $data['prefix'] . '[' . $data['name'] . ']' . $data['suffix'];
        }

        throw new InvalidArgumentException($formName . '::formName() cannot be empty for tabular inputs.');
    }

    public function getNonArrayableName(string $name): string
    {
        return substr($name, -2) === '[]' ? substr($name, 0, -2) : $name;
    }

    /**
     * Generates a hidden input field.
     *
     * @param string $name The name attribute.
     * @param bool|float|int|string|null $value The value attribute. If it is null, the value attribute will not be
     * generated.
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as the attributes of
     * the resulting tag. The values will be HTML-encoded using {@see encode()}. If a value is null,
     * the corresponding attribute will not be rendered.
     *
     * See {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @throws JsonException
     *
     * @return string The generated hidden input tag.
     */
    public function hiddenInput(string $name, $value = null, array $options = []): string
    {
        return $this->input('hidden', $name, $value, $options);
    }

    /**
     * Generates an input type of the given type.
     *
     * @param string $type The type attribute.
     * @param string|null $name The name attribute. If it is null, the name attribute will not be generated.
     * @param bool|float|int|string|null $value the value attribute. If it is null, the value attribute will
     * not be generated.
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as the attributes of
     * the resulting tag. The values will be HTML-encoded using {@see encodeAttribute()}.
     *
     * If a value is null, the corresponding attribute will not be rendered.
     *
     * See {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @throws JsonException
     *
     * @return string The generated input tag.
     */
    public function input(string $type, ?string $name = null, $value = null, array $options = []): string
    {
        if (!isset($options['type'])) {
            $options['type'] = $type;
        }

        $options['name'] = $name;
        $options['value'] = $value;

        return $this->tag('input', '', $options);
    }

    /**
     * Generates a label tag.
     *
     * @param string $content label text. It will NOT be HTML-encoded. Therefore you can pass in HTML code such as an
     * image tag. If this is is coming from end users, you should {@see encode()} it to prevent XSS attacks.
     * @param string|null $for the ID of the HTML element that this label is associated with. If this is null, the "for"
     * attribute will not be generated.
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as the attributes of
     * the resulting tag. The values will be HTML-encoded using {@see encode()}.
     *
     * If a value is null, the corresponding attribute will not be rendered.
     *
     * {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated label tag
     */
    public function label(string $content, string $for = null, array $options = [])
    {
        $options['for'] = $for;

        return $this->tag('label', $content, $options);
    }

    /**
     * Generates a list box.
     *
     * @param string $name The input name.
     * @param array|iterable|string|null $selection The selected value(s). String for single or array for multiple
     * selection(s).
     * @param array $items The option data items. The array keys are option values, and the array values are the
     * corresponding option labels. The array can also be nested (i.e. some array values are arrays too). For each
     * sub-array, an option group will be generated whose label is the key associated with the sub-array. If you have a
     * list of data models, you may convert them into the format described above using
     * {@see ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in the
     * labels will also be HTML-encoded.
     * @param array $attributes The tag attributes in terms of name-value pairs. The following attributes are specially
     * handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option. You can use an array to override the value
     *   and to set other tag attributes:
     *
     * ```php
     * ['text' => 'Please select', 'options' => ['value' => 'none', 'class' => 'prompt', 'label' => 'Select']],
     * ```
     *
     * - options: array, the attributes for the select option tags. The array keys must be valid option values, and the
     *   array values are the extra attributes for the corresponding option tags. For example,
     *
     * ```php
     * [
     *     'value1' => ['disabled' => true],
     *     'value2' => ['label' => 'value 2'],
     * ];
     * ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
     *   except that the array keys represent the optgroup labels specified in $items.
     * - unselect: string, the value that will be submitted when no option is selected.
     *   When this attribute is set, a hidden field will be generated so that if no option is selected in multiple
     *   mode, we can still obtain the posted unselect value.
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to false.
     * - encode: bool, whether to encode option prompt and option value characters. Defaults to `true`.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will be HTML-encoded.
     *
     * using {@see encode}. If a value is null, the corresponding attribute will not be rendered.
     *
     * See {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * List of supported attributes: autofocus, class, disabled, encode, encodeSpaces, form, groups, multiple, name,
     * options, prompt, size, strict, unselect.
     *
     * @throws JsonException
     *
     * @return string The generated list box tag.
     */
    public function listBox(string $name, $selection = null, array $items = [], array $attributes = []): string
    {
        if (!array_key_exists('size', $attributes)) {
            $attributes['size'] = 4;
        }

        if (!empty($attributes['multiple']) && !empty($name)) {
            $name = $this->getArrayableName($name);
        }

        $attributes['name'] = $name;

        /** @var bool|float|int|string|null */
        $unselect = $attributes['unselect'] ?? null;

        unset($attributes['unselect']);

        if ($unselect !== null) {
            // Add a hidden field so that if the list box has no option being selected, it still submits a value.
            $name = $this->getNonArrayableName($name);
            $hiddenOptions = [];

            // Make sure disabled input is not sending any value.
            if (!empty($attributes['disabled'])) {
                /** @var string */
                $hiddenOptions['disabled'] = $attributes['disabled'];
            }

            $hidden = $this->hiddenInput($name, $unselect, $hiddenOptions);
        } else {
            $hidden = '';
        }

        /**
         * @var string $selectContent
         * @var array $attributes
         */
        [$selectContent, $attributes] = $this->renderSelectOptions($selection, $items, $attributes);

        $html = $hidden . $this->tag('select', "\n" . $selectContent . "\n", $attributes);

        if (empty($selectContent)) {
            $html = $hidden . $this->tag('select', $selectContent, $attributes);
        }

        return $html;
    }

    /**
     * Renders the HTML tag attributes.
     *
     * Attributes whose values are of boolean type will be treated as
     * [boolean attributes](http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes).
     *
     * Attributes whose values are null will not be rendered.
     *
     * The values of attributes will be HTML-encoded using {@see encode()}.
     *
     * `aria` and `data` attributes get special handling when they are set to an array value. In these cases, the array
     * will be "expanded" and a list of ARIA/data attributes will be rendered. For example,
     * `'aria' => ['role' => 'checkbox', 'value' => 'true']` would be rendered as
     * `aria-role="checkbox" aria-value="true"`.
     *
     * If a nested `data` value is set to an array, it will be JSON-encoded. For example,
     * `'data' => ['params' => ['id' => 1, 'name' => 'yii']]` would be rendered as
     * `data-params='{"id":1,"name":"yii"}'`.
     *
     * @param array $attributes attributes to be rendered. The attribute values will be HTML-encoded using
     * {@see encode()}.
     *
     * @return string the rendering result. If the attributes are not empty, they will be rendered into a string with a
     * leading white space (so that it can be directly appended to the tag name in a tag). If there is no attribute, an
     * empty string will be returned.
     *
     * {@see addCssClass()}
     */
    public function renderTagAttributes(array $attributes = []): string
    {
        $html = '';

        if (count($attributes) > 1) {
            $sorted = [];
            foreach ($this->attributeOrder as $name) {
                if (isset($attributes[$name])) {
                    /** @var string[] */
                    $sorted[$name] = $attributes[$name];
                }
            }
            $attributes = array_merge($sorted, $attributes);
        }

        /** @var mixed $value */
        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= " $name";
                }
            } elseif (is_array($value)) {
                if (in_array($name, $this->dataAttributes, true)) {
                    /** @psalm-var array<array-key, array|string|\Stringable|null> $value */
                    foreach ($value as $n => $v) {
                        if (is_array($v)) {
                            $html .= " $name-$n='" . Json::htmlEncode($v) . "'";
                        } else {
                            $html .= " $name-$n=\"" . $this->encode((string) $v) . '"';
                        }
                    }
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . $this->encode(implode(' ', $value)) . '"';
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    /** @psalm-var array<string, string> $value */
                    $html .= " $name=\"" . $this->encode($this->cssStyleFromArray($value)) . '"';
                } else {
                    $html .= " $name='" . Json::htmlEncode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= " $name=\"" . $this->encode((string) $value) . '"';
            }
        }

        return $html;
    }

    /**
     * Generates a radio button input.
     *
     * @param string $name The name attribute.
     * @param bool $checked Whether the radio button should be checked.
     * @param array $options The tag options in terms of name-value pairs.
     *
     * {@see booleanInput()} for details about accepted attributes.
     *
     * @throws JsonException
     *
     * @return string The generated radio button tag.
     */
    public function radio(string $name, bool $checked = false, array $options = []): string
    {
        return $this->booleanInput('radio', $name, $checked, $options);
    }

    /**
     * Generates a submit button tag.
     *
     * @param string $content The content enclosed within the button tag. It will NOT be HTML-encoded. Therefore you
     * can pass in HTML code such as an image tag. If this is is coming from end users, you should consider
     * {@see encode()} it to prevent XSS attacks.
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as the attributes of
     * the resulting tag. The values will be HTML-encoded using {@see encode}. If a value is null, the corresponding
     * attribute will not be rendered.
     *
     * {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @throws JsonException
     *
     * @return string The generated submit button tag.
     */
    public function submitButton(string $content = 'Submit', array $options = []): string
    {
        $options['type'] = 'submit';

        return $this->button($content, $options);
    }

    public function tag(string $name, string $content = '', array $options = []): string
    {
        if ($name === '') {
            return $content;
        }

        $name = strtolower($name);

        $html = "<$name" . $this->renderTagAttributes($options) . '>';

        return $this->voidElements($name) !== '' ? $html : "$html$content</$name>";
    }

    /**
     * Generates a text input field.
     *
     * @param string $name The name attribute.
     * @param bool|float|int|string|null $value The value attribute. If it is null, the value attribute will not be
     * generated.
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using {@see encode())}.
     *
     * If a value is null, the corresponding attribute will not be rendered.
     *
     * {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @throws JsonException
     *
     * @return string The generated text input tag.
     */
    public function textInput(string $name, $value = null, array $options = []): string
    {
        return $this->input('text', $name, $value, $options);
    }

    /**
     * Generates a boolean input.
     *
     * @param string $type The input type. This can be either `radio` or `checkbox`.
     * @param string $name The name attribute.
     * @param bool $checked Whether the checkbox should be checked.
     * @param array $options The tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the checkbox. When this attribute is present,
     *   a hidden input will be generated so that if the checkbox is not checked and is submitted, the value of this
     *   attribute will still be submitted to the server via the hidden input.
     * - label: string, a label displayed next to the checkbox. It will NOT be HTML-encoded. Therefore you can pass in
     *   HTML code such as an image tag. If this is is coming from end users, you should {@see encode()}
     *   it to prevent XSS attacks.
     *   When this option is specified, the checkbox will be enclosed by a label tag.
     * - labelOptions: array, the HTML attributes for the label tag. Do not set this option unless you set the "label"
     *   option.
     * - wrapInput: bool, use when has label.
     *   if `wrapInput` is true result will be `<label><input> Label</label>`,
     *   else `<input> <label>Label</label>`
     *
     * The rest of the options will be rendered as the attributes of the resulting checkbox tag. The values will be
     * HTML-encoded using {@see encodeAttribute()}. If a value is null, the corresponding attribute will not be
     * rendered.
     *
     * {@see renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @throws JsonException
     *
     * @return string The generated checkbox tag.
     */
    private function booleanInput(string $type, string $name, bool $checked, array $options): string
    {
        $options['checked'] = $checked;

        /** @var bool|float|int|string|null */
        $uncheck = $options['uncheck'] ?? null;

        /** @var string */
        $value = array_key_exists('value', $options) ? $options['value'] : '1';

        if (isset($options['uncheck'])) {
            $hiddenOptions = [];

            if (isset($options['form'])) {
                /** @var string */
                $hiddenOptions['form'] = $options['form'];
            }

            if (!empty($options['disabled'])) {
                /** @var bool */
                $hiddenOptions['disabled'] = $options['disabled'];
            }

            $hidden = $this->hiddenInput($name, $uncheck, $hiddenOptions);

            unset($options['uncheck']);
        } else {
            $hidden = '';
        }

        /** @var string */
        $for = $options['id'] ?? null;

        /** @var string */
        $label = $options['label'] ?? '';

        /** @var array */
        $labelOptions = $options['labelOptions'] ?? [];

        /** @var bool */
        $wrapInput = $options['wrapInput'] ?? true;

        unset($options['label'], $options['labelOptions'], $options['wrapInput']);

        if (empty($label)) {
            return $hidden . $this->input($type, $name, $value, $options);
        }

        if ($wrapInput) {
            $input = $this->input($type, $name, $value, $options);

            return $hidden . $this->label($input . ' ' . $label, null, $labelOptions);
        }

        return $hidden .
            $this->input($type, $name, $value, $options) .
            $this->label($label, $for, $labelOptions);
    }

    /**
     * Converts a CSS style array into a string representation.
     *
     * For example,
     *
     * ```php
     * print_r($this->cssStyleFromArray(['width' => '100px', 'height' => '200px']));
     * // will display: 'width: 100px; height: 200px;'
     * ```
     *
     * @param array $style the CSS style array. The array keys are the CSS property names, and the array values are the
     * corresponding CSS property values.
     *
     * @return string the CSS style string. If the CSS style is empty, a null will be returned.
     */
    private function cssStyleFromArray(array $style): string
    {
        $result = '';

        /** @var mixed */
        foreach ($style as $name => $value) {
            $result .= "$name: $value; ";
        }

        return rtrim($result);
    }

    private function defaultPromptOptions(array $promptOptions): array
    {
        /** @var string */
        $promptOptions['text'] = $promptOptions['text'] ?? '';
        /** @var array */
        $promptOptions['options'] = $promptOptions['options'] ?? [];

        return $promptOptions;
    }

    private function defaultTagOptions(array $tagOptions): array
    {
        /** @var bool */
        $tagOptions['encode'] = $tagOptions['encode'] ?? true;
        /** @var bool */
        $tagOptions['encodeSpaces'] = $tagOptions['encodeSpaces'] ?? false;
        /** @var array */
        $tagOptions['groups'] = $tagOptions['groups'] ?? [];
        /** @var array */
        $tagOptions['options'] = $tagOptions['options'] ?? [];
        /** @var array */
        $tagOptions['prompt'] = $tagOptions['prompt'] ?? [];
        /** @var bool */
        $tagOptions['strict'] = $tagOptions['strict'] ?? false;

        return $tagOptions;
    }

    /**
     * Merges already existing CSS classes with new one.
     *
     * This method provides the priority for named existing classes over additional.
     *
     * @param string[] $existingClasses Already existing CSS classes.
     * @param string[] $additionalClasses CSS classes to be added.
     *
     * @return string[] merge result.
     */
    private function mergeCssClasses(array $existingClasses, array $additionalClasses): array
    {
        foreach ($additionalClasses as $key => $class) {
            if (is_int($key) && !in_array($class, $existingClasses, true)) {
                $existingClasses[] = $class;
            } elseif (!isset($existingClasses[$key])) {
                $existingClasses[$key] = $class;
            }
        }

        return array_unique($existingClasses);
    }

    /**
     * This method parses an attribute expression and returns an associative array containing real attribute name,
     * prefix and suffix.
     *
     * For example: `['name' => 'content', 'prefix' => '', 'suffix' => '[0]']`
     *
     * An attribute expression is an attribute name prefixed and/or suffixed with array indexes. It is mainly used in
     * tabular data input and/or input of array type. Below are some examples:
     *
     * - `[0]content` is used in tabular data input to represent the "content" attribute for the first model in tabular
     *    input;
     * - `dates[0]` represents the first array element of the "dates" attribute;
     * - `[0]dates[0]` represents the first array element of the "dates" attribute for the first model in tabular
     *    input.
     *
     * @param string $attribute the attribute name or expression
     *
     * @throws InvalidArgumentException if the attribute name contains non-word characters.
     *
     * @return array
     *
     * @psalm-return array<array-key,string>
     */
    private function parseAttribute(string $attribute): array
    {
        if (!preg_match('/(^|.*\])([\w\.\+]+)(\[.*|$)/u', $attribute, $matches)) {
            throw new InvalidArgumentException('Attribute name must contain word characters only.');
        }

        return [
            'name' => $matches[2],
            'prefix' => $matches[1],
            'suffix' => $matches[3],
        ];
    }

    /**
     * Renders the option tags that can be used by {@see dropDownList()} and {@see listBox()}.
     *
     * @param array|bool|float|int|iterable|string|null $selection the selected value(s). String for single or array for
     * multiple selection(s).
     * @param array $items the option data items. The array keys are option values, and the array values are the
     * corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *
     * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     * If you have a list of data models, you may convert them into the format described above using
     * {@see ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in the labels
     * will also be HTML-encoded.
     * @param array $tagOptions the $options parameter that is passed to the {@see dropDownList()} or {@see listBox()}
     * call.
     *
     * This method will take out these elements, if any: "prompt", "options" and "groups".
     *
     * See more details in {@see dropDownList()} for the explanation of these elements.
     *
     * @return array the generated list options.
     */
    private function renderSelectOptions($selection, array $items, array $tagOptions = []): array
    {
        $tagOptions = $this->defaultTagOptions($tagOptions);

        if (is_iterable($selection)) {
            /** @psalm-suppress MixedArgument */
            $selection = array_map('strval', is_array($selection) ? $selection : iterator_to_array($selection));
        } else {
            $selection = (string) $selection;
        }

        $lines = [];

        /** @var bool */
        $encode = $tagOptions['encode'];

        /** @var bool */
        $encodeSpaces = $tagOptions['encodeSpaces'];

        /** @var array */
        $prompt = $tagOptions['prompt'];

        if ($prompt !== []) {
            $prompt = $this->defaultPromptOptions($prompt);
            /** @var string */
            $promptText = $prompt['text'];
            /** @var array */
            $promptOptions = $prompt['options'];

            if ($encode) {
                $promptText = $this->encode($promptText);
            }

            if ($encodeSpaces) {
                $promptText = str_replace(' ', '&nbsp;', $promptText);
            }

            $lines[] = $this->tag('option', $promptText, $promptOptions);
        }

        /** @var array */
        $groups = $tagOptions['groups'];

        /** @var array */
        $groupAttrs = [];

        /** @var array */
        $options = $tagOptions['options'];

        unset(
            $tagOptions['encode'],
            $tagOptions['encodeSpaces'],
            $tagOptions['prompt'],
            $tagOptions['options'],
            $tagOptions['groups']
        );

        /** @var mixed $value */
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                if ($groups !== []) {
                    /** @var array */
                    $groupAttrs = $groups[$key];
                }

                $attrs = [
                    'options' => $options,
                    'groups' => $groups,
                    'encodeSpaces' => $encodeSpaces,
                    'encode' => $encode,
                    'strict' => $tagOptions['strict'],
                ];

                /**
                 *  @var string $content
                 *  @var array $attrTemp
                 */
                [$content, $attrTemp] = $this->renderSelectOptions($selection, $value, $attrs);

                $lines[] = $this->tag('optgroup', "\n" . $content . "\n", $groupAttrs);
            } else {
                /** @var array */
                $attrs = $options[$key] ?? [];
                $attrs['value'] = $key;

                if (!array_key_exists('selected', $attrs)) {
                    $attrs['selected'] = $selection !== '' &&
                        ((!is_iterable($selection) && !strcmp((string) $key, $selection))
                            || (is_iterable($selection) && ArrayHelper::isIn((string) $key, $selection)));
                }

                /** @var string */
                $text = $value;

                if ($encode) {
                    $text = $this->encode($text);
                }

                if ($encodeSpaces) {
                    $text = str_replace(' ', '&nbsp;', $text);
                }

                $lines[] = $this->tag('option', $text, $attrs);
            }
        }

        return [implode("\n", $lines), $tagOptions];
    }

    /**
     * {@see http://www.w3.org/TR/html-markup/syntax.html#void-element}
     *
     * @return string list of void elements (element name => '').
     */
    private function voidElements(string $voidElement): string
    {
        $result = '';

        if (array_key_exists($voidElement, $this->voidElement)) {
            $result = $voidElement;
        }

        return $result;
    }
}
