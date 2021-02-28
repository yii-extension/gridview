<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Widget;

use JsonException;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Html;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;
use Yii\Extension\GridView\Widget;
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
    private Html $html;
    private Inflector $inflector;
    private array $linkOptions = [];
    private string $pageAttribute = 'page';
    private int $pageSize = Pagination::DEFAULT_PAGE_SIZE;
    private string $pageSizeAttribute = 'pagesize';
    private Pagination $pagination;
    private array $requestAttributes = [];
    private array $requestQueryParams = [];
    private string $sortParams = 'sort';
    private bool $urlAbsolute = false;
    private UrlGeneratorInterface $urlGenerator;
    private UrlMatcherInterface $urlMatcher;
    private Sort $sort;

    public function __construct(
        Html $html,
        Inflector $inflector,
        UrlGeneratorInterface $urlGenerator,
        UrlMatcherInterface $urlMatcher
    ) {
        $this->html = $html;
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
     * @param array $linkOptions HTML attributes for the link in a sorter container tag which are passed to
     * {@see Sort::link()}.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function linkOptions(array $linkOptions): self
    {
        $new = clone $this;
        $new->linkOptions = $linkOptions;

        return $new;
    }

    public function pageAttribute(string $pageAttribute): self
    {
        $new = clone $this;
        $new->pageAttribute = $pageAttribute;

        return $new;
    }

    public function pageSize(int $pageSize): self
    {
        $new = clone $this;
        $new->pageSize = $pageSize;

        return $new;
    }

    /**
     * @param Pagination $pagination the pagination object that this pager is associated with.
     *
     * @return $this
     *
     * You must set this property in order to make LinkPager work.
     */
    public function pagination(Pagination $pagination): self
    {
        $new = clone $this;
        $new->pagination = $pagination;

        return $new;
    }

    public function requestAttributes(array $requestAttributes): self
    {
        $new = clone $this;
        $new->requestAttributes = $requestAttributes;

        return $new;
    }

    public function requestQueryParams(array $requestQueryParams): self
    {
        $new = clone $this;
        $new->requestQueryParams = $requestQueryParams;

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

        /** @var array */
        $definition = $attributes[$attribute];

        $directions = $this->sort->getAttributeOrders();

        if (isset($directions[$attribute])) {
            $direction = $directions[$attribute] === SORT_DESC ? SORT_ASC : SORT_DESC;
            unset($directions[$attribute]);
        } else {
            /** @var int */
            $direction = $definition['default'] ?? SORT_ASC;
        }

        if ($this->sort->isMultiSort()) {
            $directions = array_merge([$attribute => $direction], $directions);
        } else {
            $directions = [$attribute => $direction];
        }

        $sorts = [];

        /** @var array<string, int> $directions */
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
        $page = $this->pagination->getCurrentPage();
        $pageSize = $this->pagination->getPageSize();
        $linkSorterQueryParams = [];

        $linkPagerAttributes = [$this->pageAttribute => $page, $this->pageSizeAttribute => $pageSize];
        $linkSorterQueryParams[$this->sort->getSortParam()] = $this->createSorterParam($attribute);

        $params = array_merge(
            $linkPagerAttributes,
            $this->requestAttributes,
            $this->requestQueryParams,
            $linkSorterQueryParams,
            $this->requestQueryParams,
        );

        $currentRoute = $this->urlMatcher->getCurrentRoute();

        if ($currentRoute !== null) {
            $action = $currentRoute->getName();
        }

        return $this->urlGenerator->generate($action, $params);
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
            if (isset($this->linkOptions['class']) && is_string($this->linkOptions['class'])) {
                $this->linkOptions['class'] .= ' ' . $sorterClass;
            } else {
                $this->linkOptions['class'] = $sorterClass;
            }
        }

        $url = $this->createUrl($this->attribute);

        $this->linkOptions['data-sort'] = $this->createSorterParam($this->attribute);

        if (isset($this->linkOptions['label'])) {
            $label = $this->inflector->toHumanReadable((string) $this->linkOptions['label']);
            unset($this->linkOptions['label']);
        } elseif (isset($attributes[$this->attribute]['label'])) {
            $label = $this->inflector->toHumanReadable((string) $attributes[$this->attribute]['label']);
        } else {
            $label = $this->inflector->toHumanReadable($this->attribute);
        }

        if ($this->frameworkCss === self::BULMA) {
            $this->html->addCssClass($this->linkOptions, ['link' => 'has-text-link']);
        }

        return $this->html->a($label, $url, $this->linkOptions);
    }
}
