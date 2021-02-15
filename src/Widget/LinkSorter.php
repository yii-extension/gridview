<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Widget;

use JsonException;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Sort;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Strings\Inflector;

use function array_merge;
use function implode;

/**
 * LinkSorter renders a list of sort links for the given sort definition.
 *
 * LinkSorter will generate a hyperlink for every attribute declared in {@see sort}.
 *
 * For more details and usage information on LinkSorter, see the [guide article on sorting](guide:output-sorting).
 */
final class LinkSorter extends Widget
{
    private const BOOTSTRAP = 'bootstrap';
    private const BULMA = 'bulma';
    private const FRAMEWORKCSS = [
        self::BOOTSTRAP,
        self::BULMA,
    ];
    private string $attribute = '';
    private string $frameworkCss = self::BOOTSTRAP;
    private array $linkOptions = [];
    private array $options = ['class' => 'sorter'];
    private Sort $sort;
    private UrlGeneratorInterface $urlGenerator;
    private UrlMatcherInterface $urlMatcher;

    public function __construct(
        Inflector $inflector,
        UrlGeneratorInterface $urlGenerator,
        UrlMatcherInterface $urlMatcher
    ) {
        $this->inflector = $inflector;
        $this->urlGenerator = $urlGenerator;
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * Executes the widget.
     *
     * This method renders the sort links.
     */
    protected function run(): string
    {
        if ($this->sort === null) {
            throw new InvalidConfigException('The "sort" property must be set.');
        }

        return $this->renderSorterLink();
    }

    /**
     * @param string $attributes list of the attributes that support sorting. If not set, it will be determined using
     *
     * @return $this
     */
    public function attributes(string $attribute): self
    {
        $new = clone $this;
        $new->attribute = $attribute;

        return $new;
    }

    public function frameworkCss(string $frameworkCss): self
    {
        if (!in_array($frameworkCss, self::FRAMEWORKCSS)) {
            $frameworkCss = implode('", "', self::FRAMEWORKCSS);
            throw new InvalidConfigException("Invalid framework css. Valid values are: \"$frameworkCss\".");
        }

        $new = clone $this;
        $new->frameworkCss = $frameworkCss;

        return $new;
    }

    /**
     * @param array $linkOptions HTML attributes for the link in a sorter container tag which are passed to {@see Sort::link()}.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function linkOptions(array $linkOptions): self
    {
        $new = clone $this;
        $new->linkOptions = $linkOptions;

        return $new;
    }

    /**
     * @param array $options HTML attributes for the sorter container tag.
     *
     * {@see Html::ul()} for special attributes.
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function options(array $options): self
    {
        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    public function sort(Sort $sort): self
    {
        $new = clone $this;
        $new->sort = $sort;

        return $new;
    }

    /**
     * Creates the sort variable for the specified attribute.
     *
     * The newly created sort variable can be used to create a URL that will lead to sorting by the specified attribute.
     *
     * @param string $attribute the attribute name.
     *
     * @throws InvalidConfigException if the specified attribute is not defined in {@see attributes}
     *
     * @return string the value of the sort variable.
     */
    private function createSorterParam(string $attribute): string
    {
        $attributes = $this->sort->getAttributes();

        if (!isset($attributes[$attribute])) {
            throw new InvalidConfigException("Unknown attribute: $attribute");
        }

        $definition = $attributes[$attribute];

        $directions = $this->sort->getAttributeOrders();

        if (isset($directions[$attribute])) {
            $direction = $directions[$attribute] === SORT_DESC ? SORT_ASC : SORT_DESC;
            unset($directions[$attribute]);
        } else {
            $direction = $definition['default'] ?? SORT_ASC;
        }

        if ($this->sort->isMultiSort()) {
            $directions = array_merge([$attribute => $direction], $directions);
        } else {
            $directions = [$attribute => $direction];
        }

        $sorts = [];

        foreach ($directions as $attribute => $direction) {
            $sorts[] = $direction === SORT_DESC ? '-' . $attribute : $attribute;
        }

        return implode($this->sort->getSeparator(), $sorts);
    }

    /**
     * Creates a URL for sorting the data by the specified attribute.
     *
     * This method will consider the current sorting status given by {@see attributeOrders}.
     *
     * For example, if the current page already sorts the data by the specified attribute in ascending order,
     * then the URL created will lead to a page that sorts the data by the specified attribute in descending order.
     *
     * @param string $attribute the attribute name
     * @param bool $absolute whether to create an absolute URL. Defaults to `false`.
     *
     * @throws InvalidConfigException if the attribute is unknown
     *
     * @return string the URL for sorting. False if the attribute is invalid.
     *
     * {@see attributeOrders}
     * {@see params}
     */
    private function createUrl(string $attribute, bool $absolute = false): string
    {
        $action = '';
        $params[$this->sort->getSortParam()] = $this->createSorterParam($attribute);

        $currentRoute = $this->urlMatcher->getCurrentRoute();

        if ($currentRoute !== null) {
            $action = $currentRoute->getName();
        }

        return $this->urlGenerator->generate($action, ['sort' => $this->createSorterParam($attribute)]);
    }

    /**
     * Generates a hyperlink that links to the sort action to sort by the specified attribute.
     *
     * Based on the sort direction, the CSS class of the generated hyperlink will be appended with "asc" or "desc".
     *
     * There is one special attribute `label` which will be used as the label of the hyperlink.
     *
     * If this is not set, the label defined in {@see attributes} will be used.
     *
     * If no label is defined, {@see Inflector::pascalCaseToId} will be called to get a label.
     *
     * Note that it will not be HTML-encoded.
     *
     * @throws InvalidConfigException|JsonException if the attribute is unknown.
     *
     * @return string the generated hyperlink
     */
    private function renderSorterlink(): string
    {
        $attributes = $this->sort->getAttributes();
        $direction = $this->sort->getAttributeOrder($this->attribute);

        if ($direction !== null) {
            $sorterClass = $direction === SORT_DESC ? 'desc' : 'asc';
            if (isset($options['class'])) {
                $options['class'] .= ' ' . $sorterClass;
            } else {
                $options['class'] = $sorterClass;
            }
        }

        $url = $this->createUrl($this->attribute);

        $options['data-sort'] = $this->createSorterParam($this->attribute);

        if (isset($options['label'])) {
            $label = $this->inflector->toHumanReadable($options['label']);
            unset($options['label']);
        } elseif (isset($attributes[$this->attribute]['label'])) {
            $label = $this->inflector->toHumanReadable($attributes[$this->attribute]['label']);
        } else {
            $label = $this->inflector->toHumanReadable($this->attribute);
        }

        if ($this->frameworkCss === self::BULMA) {
            Html::addCssClass($options, ['link' => 'has-text-link']);
        }

        return Html::a($label, $url, array_merge($options, ['encode' => false]));
    }
}
