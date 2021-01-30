<?php

declare(strict_types=1);

namespace Yii\Extension\GridView;

use JsonException;
use Yii\Extension\GridView\DataProvider\DataProviderInterface;
use Yii\Extension\GridView\Factory\GridViewFactory;
use Yii\Extension\GridView\Widget\LinkPager;
use Yii\Extension\GridView\Widget\LinkSorter;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

/**
 * BaseListView is a base class for widgets displaying data from data provider
 * such as ListView and GridView.
 *
 * It provides features like sorting, paging and also filtering the data.
 *
 * For more details and usage information on BaseListView, see the:
 *
 * [guide article on data widgets](guide:output-data-widgets).
 */
abstract class BaseListView extends Widget
{
    /**
     * @var array the HTML attributes for the container tag of the list view. The "tag" element specifies the tag name
     * of the container element and defaults to "div".
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $options = [];

    /**
     * @var DataProviderInterface the data provider for the view. This property is required.
     */
    public DataProviderInterface $dataProvider;

    /**
     * @var array the configuration for the pager widget. By default, {@see LinkPager} will be used to render the pager.
     * You can use a different widget class by configuring the "class" element.
     * Note that the widget must support the `pagination` property which will be populated with the
     */
    public array $pager = [];

    /**
     * @var array the configuration for the sorter widget. By default, {@see LinkSorter} will be used to render the
     * sorter. You can use a different widget class by configuring the "class" element.
     * Note that the widget must support the `sort` property which will be populated with the value of the
     * {@see dataProvider} and will overwrite this value.
     */
    public array $sorter = [];

    /**
     * @var string the HTML content to be displayed as the summary of the list view.
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
     */
    public ?string $summary = null;

    /**
     * @var array the HTML attributes for the summary of the list view. The "tag" element specifies the tag name of the
     * summary element and defaults to "div".
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $summaryOptions = ['class' => 'summary'];

    /**
     * @var bool whether to show an empty list view if {@see dataProvider} returns no data. The default value is false
     * which displays an element according to the {@see emptyText} and {@see emptyTextOptions} properties.
     */
    public bool $showOnEmpty = false;

    /**
     * @var string|false the HTML content to be displayed when {@see dataProvider} does not have any data. When this is
     * set to `false` no extra HTML content will be generated.
     * The default value is the text "No results found." which will be translated to the current application language.
     * {@see showOnEmpty}
     * {@see emptyTextOptions}
     */
    public $emptyText;

    /**
     * @var array the HTML attributes for the emptyText of the list view. The "tag" element specifies the tag name of
     * the emptyText element and defaults to "div".
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public array $emptyTextOptions = ['class' => 'empty'];

    /**
     * @var string the layout that determines how different sections of the list view should be organized.
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
     */
    public string $layout = "{summary}\n{items}\n{pager}";

    /**
     * Renders the data active record classes.
     *
     * @return string the rendering result.
     */
    abstract public function renderItems(): string;

    /** @var GridViewFactory $gridViewFactory factory for data class. */
    protected GridViewFactory $gridViewFactory;

    /** @var TranslatorInterface $translator */
    protected TranslatorInterface $translator;

    public function __construct(GridViewFactory $gridViewFactory, TranslatorInterface $translator)
    {
        $this->gridViewFactory = $gridViewFactory;
        $this->translator = $translator;
    }

    /**
     * Runs the widget.
     */
    public function run(): string
    {
        if ($this->showOnEmpty || $this->dataProvider->getCount() > 0) {
            $content = preg_replace_callback('/{\\w+}/', function ($matches) {
                $content = $this->renderSection($matches[0]);

                return $content === false ? $matches[0] : $content;
            }, $this->layout);
        } else {
            $content = $this->renderEmpty();
        }

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options);
    }

    /**
     * Renders a section of the specified name. If the named section is not supported, false will be returned.
     *
     * @param string $name the section name, e.g., `{summary}`, `{items}`.
     *
     * @return string|bool the rendering result of the section, or false if the named section is not supported.
     */
    public function renderSection(string $name)
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
                return false;
        }
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
    public function renderEmpty(): string
    {
        if ($this->emptyText === false) {
            return '';
        }

        $options = $this->emptyTextOptions;
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $this->emptyText, $options);
    }

    public function renderSummary(): string
    {
        $count = $this->dataProvider->getCount();

        if ($count <= 0) {
            return '';
        }

        $summaryOptions = $this->summaryOptions;
        $tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');

        if (($pagination = $this->dataProvider->getPagination()) !== null) {
            $totalCount = $this->dataProvider->getTotalCount();
            $begin = $pagination->getPage() * $pagination->pageSize + 1;
            $end = $begin + $count - 1;

            if ($begin > $end) {
                $begin = $end;
            }

            $page = $pagination->getPage() + 1;
            $pageCount = $pagination->getPageCount();

            if (($summaryContent = $this->summary) === null) {
                return Html::tag(
                    $tag,
                    $this->translator->translate(
                        'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> ' .
                        '{totalCount, plural, one{item} other{items}}.',
                        [
                            'begin' => $begin,
                            'end' => $end,
                            'count' => $count,
                            'totalCount' => $totalCount,
                            'page' => $page,
                            'pageCount' => $pageCount,
                        ],
                        'user',
                    ),
                    $summaryOptions,
                );
            }
        } else {
            $begin = $page = $pageCount = 1;
            $end = $totalCount = $count;
            if (($summaryContent = $this->summary) === null) {
                return Html::tag(
                    $tag,
                    $this->translator->translate(
                        'Total <b>{count, number}</b> {count, plural, one{item} other{items}}.',
                        [
                            'begin' => $begin,
                            'end' => $end,
                            'count' => $count,
                            'totalCount' => $totalCount,
                            'page' => $page,
                            'pageCount' => $pageCount,
                        ],
                        'user',
                    ),
                    $summaryOptions
                );
            }
        }
    }

    /**
     * Renders the pager.
     *
     * @return string the rendering result
     */
    public function renderPager(): string
    {
        $pagination = $this->dataProvider->getPagination();

        if ($pagination === null || $this->dataProvider->getCount() <= 0) {
            return '';
        }

        /** @var $class LinkPager */
        $pager = LinkPager::widget();
        $pager->pagination = $pagination;

        return $pager->run();
    }

    /**
     * Renders the sorter.
     *
     * @return string the rendering result
     */
    public function renderSorter(): string
    {
        var_dump("aquix");
        die;

        $sort = $this->dataProvider->getSort();

        if ($sort === false || empty($sort->getAttributesOrder()) || $this->dataProvider->getCount() <= 0) {
            return '';
        }

        /* @var $class LinkSorter */
        $sorter = LinkSorter::widget();
        $sorter->sort = $sort;

        return $sorter->render();
    }
}
