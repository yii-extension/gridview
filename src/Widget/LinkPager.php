<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Widget;

use JsonException;
use Yii\Extension\GridView\Exception\InvalidConfigException;
use Yii\Extension\GridView\Helper\Html;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Widget;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\View\WebView;

use function implode;

/**
 * LinkPager displays a list of hyperlinks that lead to different pages of target.
 *
 * LinkPager works with a {@see pagination} object which specifies the total number of pages and the current page
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
    private const REL_SELF = 'self';
    private const LINK_NEXT = 'next';
    private const LINK_PREV = 'prev';
    private const LINK_FIRST = 'first';
    private const LINK_LAST = 'last';
    private const BOOTSTRAP = 'bootstrap';
    private const BULMA = 'bulma';
    private const FRAMEWORKCSS = [
        self::BOOTSTRAP,
        self::BULMA,
    ];
    private string $activePageCssClass = 'active';
    private array $buttonsContainerAttributes = [];
    public bool $disableCurrentPageButton = false;
    private array $disabledListItemSubTagAttributes = [];
    private string $disabledPageCssClass = 'disabled';
    private string $firstPageCssClass = 'page-item';
    private string $firstPageLabel = '';
    private string $frameworkCss = self::BOOTSTRAP;
    private bool $hideOnSinglePage = true;
    private Html $html;
    private string $lastPageCssClass = 'page-item';
    private string $lastPageLabel = '';
    private array $linkAttributes = ['class' => 'page-link'];
    private array $linkContainerAttributes = [];
    private int $maxButtonCount = 10;
    private array $navAttributes = ['aria-label' => 'Pagination'];
    private string $nextPageCssClass = 'page-item';
    private string $nextPageLabel = 'Next Page';
    private string $prevPageLabel = 'Previous';
    private string $pageAttribute = 'page';
    private string $pageCssClass = 'page-item';
    private string $pageSizeAttribute = 'pagesize';
    private Pagination $pagination;
    private string $prevPageCssClass = 'page-item';
    private bool $registerLinkTags = false;
    private array $requestAttributes = [];
    private array $requestQueryParams = [];
    private array $ulAttributes = ['class' => 'pagination justify-content-center mt-4'];
    private bool $urlAbsolute = false;
    private UrlGeneratorInterface $urlGenerator;
    private UrlMatcherInterface $urlMatcher;
    private WebView $webView;

    public function __construct(
        Html $html,
        UrlGeneratorInterface $urlGenerator,
        UrlMatcherInterface $urlMatcher,
        WebView $webView
    ) {
        $this->html = $html;
        $this->urlGenerator = $urlGenerator;
        $this->urlMatcher = $urlMatcher;
        $this->webView = $webView;
    }

    /**
     * Executes the widget.
     *
     * This overrides the parent implementation by displaying the generated page buttons.
     *
     * @throws InvalidConfigException|JsonException
     *
     * @return string
     */
    protected function run(): string
    {
        $this->buildWidget();

        if ($this->registerLinkTags) {
            $this->registerLinkTagsInternal();
        }

        return $this->renderPageButtons();
    }

    /**
     * @param string the CSS class for the active (currently selected) page button.
     *
     * @return $this
     */
    public function activePageCssClass(string $activePageCssClass): self
    {
        $new = clone $this;
        $new->activePageCssClass = $activePageCssClass;

        return $new;
    }

    /**
     * @param array $buttonsContainerAttributes HTML attributes which will be applied to all button containers.
     *
     * @return $this
     */
    public function buttonsContainerAttributes(array $buttonsContainerAttributes): self
    {
        $new = clone $this;
        $new->buttonsContainerAttributes = $buttonsContainerAttributes;

        return $new;
    }

    /**
     * @param bool $disableCurrentPageButton whether to render current page button as disabled.
     *
     * @return $this
     */
    public function disableCurrentPageButton(bool $disableCurrentPageButton): self
    {
        $new = clone $this;
        $new->disableCurrentPageButton = $disableCurrentPageButton;

        return $new;
    }

    /**
     * @param array $disabledListItemSubTagAttributes the options for the disabled tag to be generated inside the
     * disabled list element.
     *
     * In order to customize the html tag, please use the tag key.
     *
     * ```php
     * $disabledListItemSubTagAttributes = ['tag' => 'div', 'class' => 'disabled-div'];
     * ```
     *
     * @return $this
     */
    public function disabledListItemSubTagAttributes(array $disabledListItemSubTagAttributes): self
    {
        $new = clone $this;
        $new->disabledListItemSubTagAttributes = $disabledListItemSubTagAttributes;

        return $new;
    }

    /**
     * @param string $disabledPageCssClass the CSS class for the disabled page buttons.
     *
     * @return $this
     */
    public function disabledPageCssClass(string $disabledPageCssClass): self
    {
        $new = clone $this;
        $new->disabledPageCssClass = $disabledPageCssClass;

        return $new;
    }

    /**
     * @param string $firstPageCssClass the CSS class for the "first" page button.
     *
     * @return $this
     */
    public function firstPageCssClass(string $firstPageCssClass): self
    {
        $new = clone $this;
        $new->firstPageCssClass = $firstPageCssClass;

        return $new;
    }

    /**
     * @param string $firstPageLabel the text label for the "first" page button. Note that this will NOT be
     * HTML-encoded.
     *
     * If it's specified as true, page number will be used as label.
     *
     * Default is false that means the "first" page button will not be displayed.
     *
     * @return $this
     */
    public function firstPageLabel(string $firstPageLabel): self
    {
        $new = clone $this;
        $new->firstPageLabel = $firstPageLabel;

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
     * @param bool $hideOnSinglePage Hide widget when only one page exist.
     *
     * @return $this
     */
    public function hideOnSinglePage(bool $hideOnSinglePage): self
    {
        $new = clone $this;
        $new->hideOnSinglePage = $hideOnSinglePage;

        return $new;
    }

    /**
     * @param string $lastPageCssClass the CSS class for the "last" page button.
     *
     * @return $this
     */
    public function lastPageCssClass(string $lastPageCssClass): self
    {
        $new = clone $this;
        $new->lastPageCssClass = $lastPageCssClass;

        return $new;
    }

    /**
     * @param string $lastPageLabel the text label for the "last" page button. Note that this will NOT be HTML-encoded.
     *
     * If it's specified as true, page number will be used as label.
     *
     * Default is false that means the "last" page button will not be displayed.
     *
     * @return $this
     */
    public function lastPageLabel(string $lastPageLabel): self
    {
        $new = clone $this;
        $new->lastPageLabel = $lastPageLabel;

        return $new;
    }

    /**
     * @param array $linkAttributes HTML attributes for the link in a pager container tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function linkAttributes(array $linkAttributes): self
    {
        $new = clone $this;
        $new->linkAttributes = $linkAttributes;

        return $new;
    }

    /**
     * @param array $linkContainerAttributes HTML attributes which will be applied to all link containers.
     *
     * @return $this
     */
    public function linkContainerAttributes(array $linkContainerAttributes): self
    {
        $new = clone $this;
        $new->linkContainerAttributes = $linkContainerAttributes;

        return $new;
    }

    /**
     * @param int $maxButtonCount maximum number of page buttons that can be displayed. Defaults to 10.
     *
     * @return $this
     */
    public function maxButtonCount(int $maxButtonCount): self
    {
        $new = clone $this;
        $new->maxButtonCount = $maxButtonCount;

        return $new;
    }

    /**
     * @param string $nextPageCssClass the CSS class for the "next" page button.
     *
     * @return $this
     */
    public function nextPageCssClass(string $nextPageCssClass): self
    {
        $new = clone $this;
        $new->nextPageCssClass = $nextPageCssClass;

        return $new;
    }

    /**
     * @param string $nextPageLabel the label for the "next" page button. Note that this will NOT be HTML-encoded.
     *
     * If this property is false, the "next" page button will not be displayed.
     *
     * @return $this
     */
    public function nextPageLabel(string $nextPageLabel): self
    {
        $new = clone $this;
        $new->nextPageLabel = $nextPageLabel;

        return $new;
    }

    /**
     * @param array $navAttributes HTML attributes for the pager container tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function navAttributes(array $navAttributes): self
    {
        $new = clone $this;
        $new->navAttributes = $navAttributes;

        return $new;
    }

    public function pageAttribute(string $pageAttribute): self
    {
        $new = clone $this;
        $new->pageAttribute = $pageAttribute;

        return $new;
    }

    /**
     * @param string $pageCssClass the CSS class for the each page button.
     *
     * @return $this
     */
    public function pageCssClass(string $pageCssClass): self
    {
        $new = clone $this;
        $new->pageCssClass = $pageCssClass;

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

    /**
     * @param string $prevPageCssClass the CSS class for the "previous" page button.
     *
     * @return $this
     */
    public function prevPageCssClass(string $prevPageCssClass): self
    {
        $new = clone $this;
        $new->prevPageCssClass = $prevPageCssClass;

        return $new;
    }

    /**
     * @param string $prevPageLabel the text label for the "previous" page button. Note that this will NOT
     * be HTML-encoded.
     *
     * If this property is false, the "previous" page button will not be displayed.
     *
     * @return $this
     */
    public function prevPageLabel(string $prevPageLabel): self
    {
        $new = clone $this;
        $new->prevPageLabel = $prevPageLabel;

        return $new;
    }

    /**
     * @param bool $registerLinkTags whether to register link tags in the HTML header for prev, next, first and last
     * page.
     *
     * Defaults to `false` to avoid conflicts when multiple pagers are used on one page.
     *
     * @return $this
     *
     * @see http://www.w3.org/TR/html401/struct/links.html#h-12.1.2
     * @see registerLinkTags()
     */
    public function registerLinkTags(bool $registerLinkTags): self
    {
        $new = clone $this;
        $new->registerLinkTags = $registerLinkTags;

        return $new;
    }

    /**
     * Registers relational link tags in the html header for prev, next, first and last page.
     *
     * These links are generated using {@see pagination::getLinks()}.
     *
     * @see http://www.w3.org/TR/html401/struct/links.html#h-12.1.2
     */
    private function registerLinkTagsInternal(): void
    {
        /** @var array */
        foreach ($this->createLinks() as $rel => $href) {
            $this->webView->registerLinkTag(['rel' => $rel, 'href' => $href]);
        }
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
     * The generated urls will be absolute.
     *
     * @return $this
     */
    public function urlAbsolute(): self
    {
        $new = clone $this;
        $new->urlAbsolute = true;

        return $new;
    }

    /**
     * @param array $ulAttributes HTML attributes for the pager container tag.
     *
     * @return $this
     *
     * {@see Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public function ulAttributes(array $ulAttributes): self
    {
        $new = clone $this;
        $new->ulAttributes = $ulAttributes;

        return $new;
    }

    private function buildBulma(): void
    {
        $this->navAttributes['class'] = 'pagination is-centered mt-4';
        $this->ulAttributes['class'] = 'pagination-list justify-content-center mt-4';
        $this->linkAttributes = [];
        $this->pageCssClass = 'pagination-link';
        $this->firstPageCssClass = 'pagination-previous';
        $this->lastPageCssClass = 'pagination-next';
        $this->prevPageCssClass = 'pagination-previous has-background-link has-text-white';
        $this->nextPageCssClass = 'pagination-next has-background-link has-text-white';
        $this->activePageCssClass = 'is-current';
        $this->disabledPageCssClass = 'disabled';
    }

    private function buildWidget(): void
    {
        if ($this->frameworkCss === self::BULMA) {
            $this->buildBulma();
        }
    }

    private function createLinks(): array
    {
        $currentPage = $this->pagination->getCurrentPage();
        $pageCount = $this->pagination->getTotalPages();

        $links = [self::REL_SELF => $this->createUrl($currentPage)];

        if ($pageCount === 1) {
            $links[self::LINK_FIRST] = $this->createUrl(1);
            $links[self::LINK_LAST] = $this->createUrl($pageCount);
            if ($currentPage > 1) {
                $links[self::LINK_PREV] = $this->createUrl($currentPage);
            }
            if ($currentPage < $pageCount) {
                $links[self::LINK_NEXT] = $this->createUrl($currentPage);
            }
        }

        return $links;
    }

    /**
     * Creates the URL suitable for pagination with the specified page number. This method is mainly called by pagers
     * when creating URLs used to perform pagination.
     *
     * @param int $page the zero-based page number that the URL should point to.
     * @param int|null $pageSize the number of items on each page. If not set, the value of {@see pageSize} will be
     * used.
     * @param bool $absolute whether to create an absolute URL. Defaults to `false`.
     *
     * @return string the created URL.
     *
     * {@see params}
     * {@see forcePageParam}
     */
    private function createUrl(int $page): string
    {
        $currentRoute = $this->urlMatcher->getCurrentRoute();
        $pageSize = $this->pagination->getPageSize();
        $url = '';

        $linkPagerAttributes = [$this->pageAttribute => $page, $this->pageSizeAttribute => $pageSize];

        $params = array_merge($linkPagerAttributes, $this->requestAttributes, $this->requestQueryParams);

        if ($currentRoute !== null) {
            $action = $currentRoute->getName();
            $url = $this->urlGenerator->generate($action, $params);

            if ($this->urlAbsolute === true) {
                $url = $this->urlGenerator->generateAbsolute($action, $params);
            }
        }

        return $url;
    }

    /**
     * @return array the begin and end pages that need to be displayed.
     */
    private function getPageRange(): array
    {
        $currentPage = $this->pagination->getCurrentPage();
        $pageCount = $this->pagination->getTotalPages();

        $beginPage = max(1, $currentPage - (int) ($this->maxButtonCount / 2));

        if (($endPage = $beginPage + $this->maxButtonCount - 1) >= $pageCount) {
            $endPage = $pageCount;
            $beginPage = max(1, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }

    /**
     * Renders a page button.
     *
     * You may override this method to customize the generation of page buttons.
     *
     * @param string $label the text label for the button
     * @param int $page the page number
     * @param array $buttonsAttributes the attributes class for the page button.
     * @param bool $disabled whether this page button is disabled
     * @param bool $active whether this page button is active
     *
     * @throws JsonException
     *
     * @return string the rendering result
     */
    private function renderPageButton(
        string $label,
        int $page,
        array $buttonsAttributes,
        bool $disabled = false,
        bool $active = false
    ): string {
        /** @var string */
        $linkWrapTag = ArrayHelper::remove($buttonsAttributes, 'tag', 'li');
        $linkAttributes = $this->linkAttributes;
        $linkAttributes['data-page'] = $page;

        if ($active) {
            $this->html->addCssClass($buttonsAttributes, $this->activePageCssClass);
        }

        if ($disabled) {
            $linkAttributes['aria-disabled'] = 'true';
            $linkAttributes['tabindex'] = '-1';
        }

        if ($disabled && $this->frameworkCss === self::BOOTSTRAP) {
            $this->html->addCssClass($buttonsAttributes, $this->disabledPageCssClass);
        }

        if ($disabled && $this->frameworkCss === self::BULMA) {
            $buttonsAttributes['disabled'] = true;
        }

        return
            $this->html->beginTag($linkWrapTag, $buttonsAttributes) .
                $this->html->a($label, $this->createUrl($page), $linkAttributes) .
            $this->html->endTag($linkWrapTag);
    }

    /**
     * Renders the page buttons for framework css bulma.
     *
     * @throws JsonException
     *
     * @return string the rendering result
     */
    private function renderPageButtons(): string
    {
        $currentPage = $this->pagination->getCurrentPage();
        $pageCount = $this->pagination->getTotalPages();

        if ($pageCount < 2 && $this->hideOnSinglePage) {
            return '';
        }

        $renderFirstPageButtonLink = $this->renderFirstPageButtonLink();
        $renderPreviousPageButtonLink = $this->renderPreviousPageButtonLink($currentPage);
        $renderPageButtonLinks = $this->renderPageButtonLinks($currentPage);
        $renderNextPageButtonLink = $this->renderNextPageButtonLink($currentPage, $pageCount);
        $renderLastPageButtonLink = $this->renderLastPageButtonLink($pageCount);

        /** @var string */
        $tag = ArrayHelper::remove($this->ulAttributes, 'tag', 'ul');

        $html =
            $this->html->beginTag('nav', $this->navAttributes) . "\n" .
                $this->html->tag(
                    $tag,
                    "\n" .
                    trim($renderFirstPageButtonLink) . trim($renderPreviousPageButtonLink) .
                    implode("\n", $renderPageButtonLinks) .
                    trim($renderNextPageButtonLink) . trim($renderLastPageButtonLink) .
                    "\n",
                    $this->ulAttributes
                ) . "\n" .
            $this->html->endTag('nav') . "\n";

        if ($this->frameworkCss === self::BULMA) {
            $html =
                $this->html->beginTag('nav', $this->navAttributes) . "\n" .
                    trim($renderFirstPageButtonLink) . trim($renderPreviousPageButtonLink) .
                    $this->html->tag($tag, "\n" . implode("\n", $renderPageButtonLinks), $this->ulAttributes) .
                    trim($renderNextPageButtonLink) . trim($renderLastPageButtonLink) . "\n" .
                $this->html->endTag('nav');
        }

        return $html;
    }

    private function renderFirstPageButtonLink(): string
    {
        $html = '';

        if ($this->firstPageLabel !== '') {
            $html = $this->renderPageButton($this->firstPageLabel, 1, ['class' => $this->firstPageCssClass]);
        }

        return $html;
    }

    private function renderPreviousPageButtonLink(int $currentPage): string
    {
        $html = '';

        if ($this->prevPageLabel !== '') {
            $html = $this->renderPageButton(
                $this->prevPageLabel,
                max($currentPage - 1, 1),
                ['class' => $this->prevPageCssClass],
                $currentPage === 1,
            );
        }

        return $html;
    }

    private function renderPageButtonLinks(int $currentPage): array
    {
        $buttons = [];

        /**
         * link buttons pages
         *
         * @var int $beginPage
         * @var int $endPage
         */
        [$beginPage, $endPage] = $this->getPageRange();

        $this->html->addCssClass($this->buttonsContainerAttributes, $this->pageCssClass);

        for ($i = $beginPage; $i <= $endPage; ++$i) {
            $buttons[] = $this->renderPageButton(
                (string) $i,
                $i,
                $this->buttonsContainerAttributes,
                $this->disableCurrentPageButton && $i === $currentPage,
                $i === $currentPage,
            );
        }

        return $buttons;
    }

    private function renderNextPageButtonLink(int $currentPage, int $pageCount): string
    {
        $html = '';

        if ($this->nextPageLabel !== '') {
            $html = $this->renderPageButton(
                $this->nextPageLabel,
                min($pageCount, $currentPage + 1),
                ['class' => $this->nextPageCssClass],
                $currentPage === $pageCount,
            );
        }

        return $html;
    }

    private function renderLastPageButtonLink(int $pageCount): string
    {
        $html = '';

        if ($this->lastPageLabel !== '') {
            $html = $this->renderPageButton($this->lastPageLabel, $pageCount, ['class' => $this->lastPageCssClass]);
        }

        return $html;
    }
}
