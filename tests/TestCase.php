<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yii\Extension\GridView\Column\ActionColumn;
use Yii\Extension\GridView\Column\CheckboxColumn;
use Yii\Extension\GridView\Column\DataColumn;
use Yii\Extension\GridView\DataProvider\ArrayDataProvider;
use Yii\Extension\GridView\DataProvider\DataProvider;
use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Di\Container;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\FastRoute\UrlMatcher;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Formatter\Intl\IntlMessageFormatter;
use Yiisoft\Translator\MessageFormatterInterface;
use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Widget\WidgetFactory;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected ActionColumn $actionColumn;
    protected CheckBoxColumn $checkBoxColumn;
    protected DataColumn $dataColumn;
    private ContainerInterface $container;
    private Pagination $pagination;
    private Sort $sort;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->actionColumn,
            $this->checkBoxColumn,
            $this->dataColumn,
            $this->container,
            $this->pagination,
            $this->sort,
        );
    }

    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    protected function createGridView(
        array $columns = [],
        int $currentPage = 0,
        int $pageSize = 0,
        string $frameworkCss = 'bootstrap'
    ): GridView {
        return GridView::widget()
            ->columns($columns)
            ->dataProvider($this->createDataProvider())
            ->currentPage($currentPage)
            ->pageSize($pageSize);
    }

    protected function createDataProvider(array $sortParams = []): DataProvider
    {
        $data = [
            ['id' => 1, 'username' => 'tests 1', 'total' => '10'],
            ['id' => 2, 'username' => 'tests 2', 'total' => '20'],
            ['id' => 3, 'username' => 'tests 3', 'total' => '30'],
            ['id' => 4, 'username' => 'tests 4', 'total' => '40'],
            ['id' => 5, 'username' => 'tests 5', 'total' => '50'],
            ['id' => 6, 'username' => 'tests 6', 'total' => '60'],
            ['id' => 7, 'username' => 'tests 7', 'total' => '70'],
            ['id' => 8, 'username' => 'tests 8', 'total' => '80'],
            ['id' => 9, 'username' => 'tests 9', 'total' => '90'],
        ];

        $dataProvider = new ArrayDataProvider();
        //$this->sort->attributes(['id', 'username'])->params($sortParams)->enableMultiSort(true);
        $dataProvider = $dataProvider->allData($data)->pagination($this->pagination);

        return $dataProvider;
    }

    private function configContainer(): void
    {
        $this->container = new Container($this->config());

        WidgetFactory::initialize($this->container, []);

        $this->actionColumn = $this->container->get(ActionColumn::class);
        $this->checkboxColumn = $this->container->get(CheckboxColumn::class);
        $this->dataColumn = $this->container->get(DataColumn::class);
        $this->pagination = $this->container->get(Pagination::class);
        $this->sort = $this->container->get(Sort::class);
    }

    private function config(): array
    {
        return [
            Aliases::class => [
                '__class' => Aliases::class,
                '__construct()' => [['@grid-view-translation' => dirname(__DIR__) . '/src/Translation']],
            ],

            LoggerInterface::class => NullLogger::class,

            ListenerProviderInterface::class => Provider::class,

            EventDispatcherInterface::class => Dispatcher::class,

            WebView::class => [
                '__class' => WebView::class,
                '__construct()' => [
                    'basePath' => __DIR__ . '/runtime',
                ],
            ],

            UrlGeneratorInterface::class => UrlGenerator::class,

            UrlMatcherInterface::class => UrlMatcher::class,

            RouteCollectorInterface::class => Group::create(
                null,
                [
                    Route::methods(['GET', 'POST'], '/admin/delete[/{id}]', [TestDelete::class, 'run'])
                        ->name('delete'),
                    Route::methods(['GET', 'POST'], '/admin/update[/{id}]', [TestUpdate::class, 'run'])
                        ->name('update'),
                    Route::methods(['GET', 'POST'], '/admin/view[/{id}]', [TestView::class, 'run'])
                        ->name('view'),
                    Route::methods(['GET', 'POST'], '/admin/custom[/{id}]', [TestCustom::class, 'run'])
                        ->name('admin/custom'),
                ]
            ),

            RouteCollectionInterface::class => RouteCollection::class,

            MessageReaderInterface::class => [
                '__class' => MessageSource::class,
                '__construct()' => [fn (Aliases $aliases) => $aliases->get('@grid-view-translation')],
            ],

            MessageFormatterInterface::class => IntlMessageFormatter::class,

            CategorySource::class => [
                '__class' => CategorySource::class,
                '__construct()' => [
                    'name' => 'yii-gridview',
                ],
            ],

            TranslatorInterface::class => [
                '__class' => Translator:: class,
                '__construct()' => [
                    'locale' => 'en',
                ],
                'addCategorySource()' => [Reference::to(CategorySource::class)],
            ],
        ];
    }
}
