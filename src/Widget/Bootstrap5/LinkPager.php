<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Widget\Bootstrap5;

use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Widget\Widget;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * LinkPager displays a list of hyperlinks that lead to different pages of target.
 *
 * LinkPager works with a {@see Pagination} object which specifies the total number of pages and the current page
 * number.
 *
 * Note that LinkPager only generates the necessary HTML markups. In order for it to look like a real pager, you
 * should provide some CSS styles for it.
 *
 * With the default configuration, LinkPager should look good using Twitter Bootstrap CSS framework.
 *
 * For more details and usage information on LinkPager, see the [guide article on pagination](guide:output-pagination).
 */
final class LinkPager extends Widget
{
    /**
     * @var Pagination the pagination object that this pager is associated with.
     * You must set this property in order to make LinkPager work.
     */
    private Pagination $pagination;

    /**
     * @var array HTML attributes for the pager container tag.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    private array $options = ['class' => 'pagination justify-content-center mt-4'];

    /**
     * @var array HTML attributes which will be applied to all link containers
     */
    private array $linkContainerOptions = [];

    /**
     * @var array HTML attributes for the link in a pager container tag.
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    private array $linkOptions = ['class' => 'page-link'];

    /**
     * @var string the CSS class for the each page button.
     */
    private string $pageCssClass = 'page-item';

    /**
     * @var string the CSS class for the "first" page button.
     */
    private string $firstPageCssClass = 'page-item';

    /**
     * @var string the CSS class for the "last" page button.
     */
    private string $lastPageCssClass = 'page-item';

    /**
     * @var string the CSS class for the "previous" page button.
     */
    private string $prevPageCssClass = 'page-item';

    /**
     * @var string the CSS class for the "next" page button.
     */
    private string $nextPageCssClass = 'page-item';

    /**
     * @var string the CSS class for the active (currently selected) page button.
     */
    private string $activePageCssClass = 'active';

    /**
     * @var string the CSS class for the disabled page buttons.
     */
    private string $disabledPageCssClass = 'disabled';

    /**
     * @var array the options for the disabled tag to be generated inside the disabled list element.
     * In order to customize the html tag, please use the tag key.
     *
     * ```php
     * $disabledListItemSubTagOptions = ['tag' => 'div', 'class' => 'disabled-div'];
     * ```
     */
    private array $disabledListItemSubTagOptions = [];

    /**
     * @var int maximum number of page buttons that can be displayed. Defaults to 10.
     */
    private int $maxButtonCount = 10;

    /**
     * @var string|null the label for the "next" page button. Note that this will NOT be HTML-encoded.
     * If this property is false, the "next" page button will not be displayed.
     */
    private ?string $nextPageLabel = 'Next Page';

    /**
     * @var string|null the text label for the "previous" page button. Note that this will NOT be HTML-encoded.
     * If this property is false, the "previous" page button will not be displayed.
     */
    private ?string $prevPageLabel = 'Previous';

    /**
     * @var string|null the text label for the "first" page button. Note that this will NOT be HTML-encoded.
     * If it's specified as true, page number will be used as label.
     * Default is false that means the "first" page button will not be displayed.
     */
    public ?string $firstPageLabel = null;

    /**
     * @var string|null the text label for the "last" page button. Note that this will NOT be HTML-encoded.
     * If it's specified as true, page number will be used as label.
     * Default is false that means the "last" page button will not be displayed.
     */
    public ?string $lastPageLabel = null;

    /**
     * @var bool whether to register link tags in the HTML header for prev, next, first and last page.
     * Defaults to `false` to avoid conflicts when multiple pagers are used on one page.
     *
     * @see http://www.w3.org/TR/html401/struct/links.html#h-12.1.2
     * @see registerLinkTags()
     */
    public bool $registerLinkTags = true;

    /**
     * @var bool Hide widget when only one page exist.
     */
    public bool $hideOnSinglePage = true;

    /**
     * @var bool whether to render current page button as disabled.
     */
    public bool $disableCurrentPageButton = false;

    public function __construct(WebView $webView)
    {
        $this->webView = $webView;
    }

    /**
     * Executes the widget.
     *
     * This overrides the parent implementation by displaying the generated page buttons.
     */
    public function run(): string
    {

        if ($this->pagination === null) {
            throw new InvalidConfigException('The "pagination" property must be set.');
        }

        if ($this->registerLinkTags) {
            $this->registerLinkTags();
        }

        return $this->renderPageButtons();
    }

    /**
     * Registers relational link tags in the html header for prev, next, first and last page.
     *
     * These links are generated using {@see Pagination::getLinks()}.
     *
     * {@see http://www.w3.org/TR/html401/struct/links.html#h-12.1.2}
     */
    protected function registerLinkTags()
    {
        foreach ($this->pagination->getLinks() as $rel => $href) {
            $this->webView->registerLinkTag(['rel' => $rel, 'href' => $href]);
        }
    }

    /**
     * Renders the page buttons.
     *
     * @return string the rendering result
     */
    protected function renderPageButtons(): string
    {
        $pageCount = $this->pagination->getPageCount();

        if ($pageCount < 2 && $this->hideOnSinglePage) {
            return '';
        }

        $buttons = [];
        $currentPage = $this->pagination->getPage();

        // first page
        $firstPageLabel = $this->firstPageLabel !== null ? '1' : $this->firstPageLabel;
        if ($firstPageLabel !== null) {
            $buttons[] = $this->renderPageButton(
                $firstPageLabel,
                0,
                $this->firstPageCssClass,
                $currentPage <= 0,
                false,
            );
        }

        // prev page
        if ($this->prevPageLabel !== null) {
            if (($page = $currentPage - 1) < 0) {
                $page = 0;
            }
            $buttons[] = $this->renderPageButton(
                $this->prevPageLabel,
                $page,
                $this->prevPageCssClass,
                $currentPage <= 0,
                false,
            );
        }

        // internal pages
        [$beginPage, $endPage] = $this->getPageRange();
        for ($i = $beginPage; $i <= $endPage; ++$i) {
            $buttons[] = $this->renderPageButton(
                $i + 1,
                $i,
                null,
                $this->disableCurrentPageButton && $i == $currentPage,
                $i == $currentPage,
            );
        }

        // next page
        if ($this->nextPageLabel !== null) {
            if (($page = $currentPage + 1) >= $pageCount - 1) {
                $page = $pageCount - 1;
            }

            $buttons[] = $this->renderPageButton(
                $this->nextPageLabel,
                $page,
                $this->nextPageCssClass,
                $currentPage >= $pageCount - 1,
                false,
            );
        }

        // last page
        $lastPageLabel = $this->lastPageLabel !== null ? $pageCount : $this->lastPageLabel;
        if ($lastPageLabel !== null) {
            $buttons[] = $this->renderPageButton(
                $lastPageLabel,
                $pageCount - 1,
                $this->lastPageCssClass,
                $currentPage >= $pageCount - 1,
                false,
            );
        }

        $options = $this->options;

        $tag = ArrayHelper::remove($options, 'tag', 'ul');

        return Html::tag($tag, implode("\n", $buttons), $options);
    }

    /**
     * Renders a page button.
     *
     * You may override this method to customize the generation of page buttons.
     *
     * @param string $label the text label for the button
     * @param int $page the page number
     * @param string $class the CSS class for the page button.
     * @param bool $disabled whether this page button is disabled
     * @param bool $active whether this page button is active
     *
     * @return string the rendering result
     */
    protected function renderPageButton($label, $page, $class, $disabled, $active): string
    {
        $options = $this->linkContainerOptions;
        $linkWrapTag = ArrayHelper::remove($options, 'tag', 'li');

        Html::addCssClass($options, empty($class) ? $this->pageCssClass : $class);

        if ($active) {
            Html::addCssClass($options, $this->activePageCssClass);
        }

        if ($disabled) {
            Html::addCssClass($options, $this->disabledPageCssClass);
            $disabledItemOptions = $this->disabledListItemSubTagOptions;
            Html::addCssClass($options, 'page-link');
            $tag = ArrayHelper::remove($disabledItemOptions, 'tag', 'span');

            return Html::tag($linkWrapTag, Html::tag($tag, $label, $disabledItemOptions), $options);
        }

        $linkOptions = $this->linkOptions;
        $linkOptions['data-page'] = $page;

        return Html::tag(
            $linkWrapTag,
            Html::a(
                (string) $label,
                $this->pagination->createUrl($page),
                $linkOptions
            ),
            $options
        );
    }

    /**
     * @return array the begin and end pages that need to be displayed.
     */
    protected function getPageRange(): array
    {
        $currentPage = $this->pagination->getPage();
        $pageCount = $this->pagination->getPageCount();

        $beginPage = max(0, $currentPage - (int) ($this->maxButtonCount / 2));
        if (($endPage = $beginPage + $this->maxButtonCount - 1) >= $pageCount) {
            $endPage = $pageCount - 1;
            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }

    public function activePageCssClass(string $activePageCssClass): self
    {
        $this->activePageCssClass = $activePageCssClass;

        return $this;
    }

    public function buttonsContainerOptions(array $buttonsContainerOptions): self
    {
        $this->buttonsContainerOptions = $buttonsContainerOptions;

        return $this;
    }

    public function disableCurrentPageButton(bool $disableCurrentPageButton): self
    {
        $this->disableCurrentPageButton = $disableCurrentPageButton;

        return $this;
    }

    public function disabledListItemSubTagOptions(array $disabledListItemSubTagOptions): self
    {
        $this->disabledListItemSubTagOptions = $disabledListItemSubTagOptions;

        return $this;
    }

    public function disabledPageCssClass(string $disabledPageCssClass): self
    {
        $this->disabledPageCssClass = $disabledPageCssClass;

        return $this;
    }

    public function firstPageCssClass(string $firstPageCssClass): self
    {
        $this->firstPageCssClass = $firstPageCssClass;

        return $this;
    }

    public function firstPageLabel(?string $firstPageLabel): self
    {
        $this->firstPageLabel = $firstPageLabel;

        return $this;
    }

    public function hideOnSinglePage(bool $hideOnSinglePage): self
    {
        $this->hideOnSinglePage = $hideOnSinglePage;

        return $this;
    }

    public function lastPageCssClass(string $lastPageCssClass)
    {
        $this->lastPageCssClass = $lastPageCssClass;

        return $this;
    }

    public function lastPageLabel(?string $lastPageLabel): self
    {
        $this->lastPageLabel = $lastPageLabel;

        return $this;
    }

    public function linkOptions(array $linkOptions): self
    {
        $this->linkOptions = $linkOptions;

        return $this;
    }

    public function maxButtonCount(int $maxButtonCount): self
    {
        $this->maxButtonCount = $maxButtonCount;

        return $this;
    }

    public function nextPageCssClass(string $nextPageCssClass): self
    {
        $this->nextPageCssClass = $nextPageCssClass;

        return $this;
    }

    public function nextPageLabel($nextPageLabel): self
    {
        $this->nextPageLabel = $nextPageLabel;

        return $this;
    }

    public function optionsNav(array $optionsNav): self
    {
        $this->optionsNav = $optionsNav;

        return $this;
    }

    public function optionsUl(array $optionsUl)
    {
        $this->optionsUl = $optionsUl;

        return $this;
    }

    public function pageCssClass(string $pageCssClass): self
    {
        $this->pageCssClass = $pageCssClass;

        return $this;
    }

    public function pagination(Pagination $pagination): self
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function prevPageCssClass(string $prevPageCssClass): self
    {
        $this->prevPageCssClass = $prevPageCssClass;

        return $this;
    }

    public function prevPageLabel(?string $prevPageLabel)
    {
        $this->prevPageLabel = $prevPageLabel;

        return $this;
    }
}
