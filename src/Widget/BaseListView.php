<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Widget;

use JsonException;
use Yii\Extension\GridView\Factory\GridViewFactory;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;
use Yii\Extension\GridView\DataProvider\DataProviderInterface;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

/**
 * BaseListView is a base class for widgets displaying data from data provider such as ListView and GridView.
 *
 * It provides features like sorting, paging and also filtering the data.
 *
 * For more details and usage information on BaseListView, see the:
 *
 * [guide article on data widgets](guide:output-data-widgets).
 */
abstract class BaseListView extends Widget
{
    public const BOOTSTRAP = 'bootstrap';
    public const BULMA = 'bulma';
    protected string $frameworkCss = self::BOOTSTRAP;
    protected string $emptyText = 'No results found.';
    protected string $layout = "{items}\n{summary}\n{pager}";
    protected array $options = [];
    protected DataProviderInterface $dataProvider;
    protected GridViewFactory $gridViewFactory;
    protected TranslatorInterface $translator;
    protected Pagination $pagination;
    private const FRAMEWORKCSS = [
        self::BOOTSTRAP,
        self::BULMA,
    ];
    private int $currentPage = 0;
    private bool $encloseByContainer = false;
    private array $encloseByContainerOptions = [];
    private array $emptyTextOptions = ['class' => 'empty'];
    private int $pageSize = Pagination::DEFAULT_PAGE_SIZE;
    private array $requestAttributes = [];
    private array $requestQueryParams = [];
    private bool $showOnEmpty = false;
    private string $summary = 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> ' .
        '{totalCount, plural, one{item} other{items}}';
    private array $summaryOptions = ['class' => 'summary'];

    public function __construct(GridViewFactory $gridViewFactory, TranslatorInterface $translator)
    {
        $this->gridViewFactory = $gridViewFactory;
        $this->translator = $translator;
    }

    /**
     * Renders the data active record classes.
     *
     * @return string the rendering result.
     */
    abstract protected function renderItems(): string;

    protected function run(): string
    {
        $pagination = $this->getPagination();
        $pagination->currentPage($this->currentPage);

        if ($this->pageSize > 0) {
            $pagination->pageSize($this->pageSize);
        }

        if ($this->showOnEmpty || $this->dataProvider->getCount() > 0) {
            $content = preg_replace_callback('/{\\w+}/', function ($matches) {

                $content = $this->renderSection($matches[0]);

                return $content;
            }, $this->layout);
        } else {
            $content = $this->renderEmpty();
        }

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        $html = Html::tag($tag, $content, array_merge($options, ['encode' => false]));

        if ($this->encloseByContainer) {
            $html =
                Html::beginTag('div', $this->encloseByContainerOptions)  . "\n" .
                    Html::tag($tag, $content, array_merge($options, ['encode' => false])) . "\n" .
                Html::endTag('div') . "\n";
        }

        return $html;
    }

    public function currentPage(int $currentPage): self
    {
        $new = clone $this;
        $new->currentPage = $currentPage;

        return $new;
    }

    public function encloseByContainer(): self
    {
        $new = clone $this;
        $new->encloseByContainer = true;

        return $new;
    }

    public function encloseByContainerOptions(array $encloseByContainerOptions): self
    {
        $new = clone $this;
        $new->encloseByContainerOptions = $encloseByContainerOptions;

        return $new;
    }

    /**
     * @param DataProviderInterface $dataProvider the data provider for the view. This property is required.
     *
     * @return $this
     */
    public function dataProvider(DataProviderInterface $dataProvider): self
    {
        $new = clone $this;
        $new->dataProvider = $dataProvider;

        return $new;
    }

    /**
     * @param string $emptyText the HTML content to be displayed when {@see dataProvider} does not have any data.
     *
     * The default value is the text "No results found." which will be translated to the current application language.
     *
     * @return $this
     *
     * {@see notShowOnEmpty}
     * {@see emptyTextOptions}
     */
    public function emptyText(string $emptyText): self
    {
        $new = clone $this;
        $new->emptyText = $emptyText;

        return $new;
    }

    /**
     * @param array $emptyTextOptions the HTML attributes for the emptyText of the list view.
     *
     * The "tag" element specifies the tag name of the emptyText element and defaults to "div".
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function emptyTextOptions(array $emptyTextOptions): self
    {
        $new = clone $this;
        $new->emptyTextOptions = $emptyTextOptions;

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

    public function getDataProvider(): DataProviderInterface
    {
        return $this->dataProvider;
    }

    public function getFrameworkCss(): string
    {
        return $this->frameworkCss;
    }

    public function getPagination(): Pagination
    {
        return $this->dataProvider->getPagination();
    }

    public function getRequestAttributes(): array
    {
        return $this->requestAttributes;
    }

    public function getRequestQueryParams(): array
    {
        return $this->requestQueryParams;
    }

    public function getSort(): ?Sort
    {
        return $this->dataProvider->getSort();
    }

    /**
     * @param string $layout the layout that determines how different sections of the list view should be organized.
     *
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. {@see renderSummary()}.
     * - `{items}`: the list items. {@see renderItems()}.
     * - `{sorter}`: the sorter. {@see renderSorter()}.
     * - `{pager}`: the pager. {@see renderPager()}.
     *
     * @return $this
     */
    public function layout(string $layout): self
    {
        $new = clone $this;
        $new->layout = $layout;

        return $new;
    }

    public function pageSize(int $pageSize): self
    {
        $new = clone $this;
        $new->pageSize = $pageSize;

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

    /**
     * Whether to show an empty list view if {@see dataProvider} returns no data.
     *
     * @return $this
     */
    public function showOnEmpty(): self
    {
        $new = clone $this;
        $new->showOnEmpty = true;

        return $new;
    }

    /**
     * @param array $options the HTML attributes for the container tag of the list view. The "tag" element specifies
     * the tag name of the container element and defaults to "div".
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
     * @param string $summary the HTML content to be displayed as the summary of the list view.
     *
     * If you do not want to show the summary, you may set it with an empty string.
     *
     * The following tokens will be replaced with the corresponding values:
     *
     * - `{begin}`: the starting row number (1-based) currently being displayed
     * - `{end}`: the ending row number (1-based) currently being displayed
     * - `{count}`: the number of rows currently being displayed
     * - `{totalCount}`: the total number of rows available
     * - `{page}`: the page number (1-based) current being displayed
     * - `{pageCount}`: the number of pages available
     *
     * @return $this
     */
    public function summary(string $summary): self
    {
        $new = clone $this;
        $new->summary = $summary;

        return $new;
    }

    /**
     * @param array $summaryOptions the HTML attributes for the summary of the list view. The "tag" element specifies
     * the tag name of the summary element and defaults to "div".
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function summaryOptions(array $summaryOptions): self
    {
        $new = clone $this;
        $new->summaryOptions = $summaryOptions;

        return $new;
    }

    /**
     * Renders the HTML content indicating that the list view has no data.
     *
     * @throws JsonException
     *
     * @return string the rendering result
     *
     * {@see emptyText}
     */
    protected function renderEmpty(): string
    {
        if ($this->emptyText === '') {
            return '';
        }

        $options = $this->emptyTextOptions;
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $this->emptyText, $options);
    }

    /**
     * Renders a section of the specified name. If the named section is not supported, empty string will be returned.
     *
     * @param string $name the section name, e.g., `{summary}`, `{items}`.
     *
     * @throws InvalidConfigException|JsonException
     *
     * @return string the rendering result of the section, or false if the named section is not supported.
     */
    protected function renderSection(string $name): string
    {
        switch ($name) {
            case '{summary}':
                return $this->renderSummary();
            case '{items}':
                return $this->renderItems();
            case '{pager}':
                return $this->renderPager();
            case '{sorter}':
                return $this->renderSorter();
            default:
                return '';
        }
    }

    /**
     * Renders the pager.
     *
     * @throws JsonException|InvalidConfigException
     *
     * @return string the rendering result
     */
    private function renderPager(): string
    {
        $pagination = $this->dataProvider->getPagination();

        if ($pagination === null || $this->dataProvider->getCount() < 0) {
            return '';
        }

        return LinkPager::widget()
            ->frameworkCss($this->frameworkCss)
            ->requestAttributes($this->requestAttributes)
            ->requestQueryParams($this->requestQueryParams)
            ->pagination($pagination)
            ->render();
    }

    /**
     * Renders the sorter.
     *
     * @throws InvalidConfigException
     *
     * @return string the rendering result
     */
    private function renderSorter(): string
    {
        $sort = $this->dataProvider->getSort();

        if ($sort === null || empty($sort->getAttributeOrders()) || $this->dataProvider->getCount() <= 0) {
            return '';
        }

        return LinkSorter::widget()->sort($sort)->frameworkCss($this->frameworkCss)->render();
    }

    private function renderSummary(): string
    {
        $count = $this->dataProvider->getCount();
        $pagination = $this->getPagination();

        if ($count <= 0) {
            return '';
        }

        $summaryOptions = $this->summaryOptions;
        $summaryOptions['encode'] = false;
        $tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');

        if ($pagination) {
            $totalCount = $this->dataProvider->getTotalCount();
            $begin = ($pagination->getOffset() + 1);
            $end = $begin + $count - 1;

            if ($begin > $end) {
                $begin = $end;
            }

            $page = $pagination->getCurrentPage();
            $pageCount = $pagination->getTotalPages();
        } else {
            $begin = $page = $pageCount = 1;
            $end = $totalCount = $count;
        }

        return Html::tag(
            $tag,
            $this->translator->translate(
                $this->summary,
                [
                    'begin' => $begin,
                    'end' => $end,
                    'count' => $count,
                    'totalCount' => $totalCount,
                    'page' => $page,
                    'pageCount' => $pageCount,
                ],
                'yii-gridview',
            ),
            $summaryOptions
        );
    }
}
