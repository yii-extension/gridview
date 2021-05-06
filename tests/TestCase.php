<?php

declare(strict_types=1);

namespace Yii\Extension\GridView\Tests;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionObject;
use Yii\Extension\GridView\Column\ActionColumn;
use Yii\Extension\GridView\Column\CheckboxColumn;
use Yii\Extension\GridView\Column\RadioButtonColumn;
use Yii\Extension\GridView\GridView;
use Yii\Extension\GridView\Factory\GridViewFactory;
use Yii\Extension\GridView\Helper\Html;
use Yii\Extension\GridView\Helper\Pagination;
use Yii\Extension\GridView\Helper\Sort;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Di\Container;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Factory\Definition\Reference;
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
    protected CheckboxColumn $checkboxColumn;
    protected GridViewFactory $gridViewFactory;
    protected Html $html;
    protected Pagination $pagination;
    protected RadioButtonColumn $radioButtonColumn;
    protected Sort $sort;
    protected UrlGeneratorInterface $urlGenerator;
    protected UrlMatcherInterface $urlMatcher;
    private ContainerInterface $container;

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
            $this->container,
            $this->gridViewFactory,
            $this->html,
            $this->pagination,
            $this->radioButtonColumn,
            $this->urlGenerator,
            $this->urlMatcher,
            $this->sort
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
        int $currentPage = 1,
        int $pageSize = 10,
        string $frameworkCss = 'bootstrap'
    ): GridView {
        return GridView::widget()->columns($columns)->currentPage($currentPage)->pageSize($pageSize);
    }

    protected function getArrayData(): array
    {
        return [
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
    }

    /**
     * Gets an inaccessible object property.
     *
     * @param object $object
     * @param string $propertyName
     * @param bool $revoke whether to make property inaccessible after getting.
     *
     * @return mixed
     */
    protected function getInaccessibleProperty(object $object, string $propertyName, bool $revoke = true)
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);

        $property->setAccessible(true);

        $result = $property->getValue($object);

        if ($revoke) {
            $property->setAccessible(false);
        }

        return $result;
    }

    /**
     * Invokes a inaccessible method.
     *
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $reflection = new ReflectionObject($object);

        $method = $reflection->getMethod($method);

        $method->setAccessible(true);

        $result = $method->invokeArgs($object, $args);

        if ($revoke) {
            $method->setAccessible(false);
        }

        return $result;
    }

    private function configContainer(): void
    {
        $this->container = new Container($this->config());

        WidgetFactory::initialize($this->container, []);

        $this->actionColumn = $this->container->get(ActionColumn::class);
        $this->checkboxColumn = $this->container->get(CheckboxColumn::class);
        $this->gridViewFactory = $this->container->get(GridViewFactory::class);
        $this->html = $this->container->get(Html::class);
        $this->pagination = $this->container->get(Pagination::class);
        $this->radioButtonColumn = $this->container->get(RadioButtonColumn::class);
        $this->sort = $this->container->get(Sort::class);
        $this->urlGenerator = $this->container->get(UrlGeneratorInterface::class);
        $this->urlMatcher = $this->container->get(UrlMatcherInterface::class);
    }

    private function config(): array
    {
        return [
            Aliases::class => [
                'class' => Aliases::class,
                '__construct()' => [['@grid-view-translation' => dirname(__DIR__) . '/src/Translation']],
            ],

            LoggerInterface::class => NullLogger::class,

            ListenerProviderInterface::class => Provider::class,

            EventDispatcherInterface::class => Dispatcher::class,

            WebView::class => [
                'class' => WebView::class,
                '__construct()' => [
                    'basePath' => __DIR__ . '/runtime',
                ],
            ],

            UrlGeneratorInterface::class => UrlGenerator::class,

            UrlMatcherInterface::class => UrlMatcher::class,

            RouteCollectorInterface::class => Group::create(null)
                ->routes(
                    Route::methods(['GET', 'POST'], '/admin/index')
                        ->name('admin')
                        ->action([TestDelete::class, 'run']),
                    Route::methods(['GET', 'POST'], '/admin/delete[/{id}]')
                        ->name('delete')
                        ->action([TestDelete::class, 'run']),
                    Route::methods(['GET', 'POST'], '/admin/update[/{id}]')
                        ->name('update')
                        ->action([TestUpdate::class, 'run']),
                    Route::methods(['GET', 'POST'], '/admin/view[/{id}]')
                        ->action([TestView::class, 'run'])
                        ->name('view'),
                    Route::methods(['GET', 'POST'], '/admin/custom[/{id}]')
                        ->name('admin/custom')
                        ->action([TestCustom::class, 'run']),
                ),

            RouteCollectionInterface::class => RouteCollection::class,

            MessageReaderInterface::class => [
                'class' => MessageSource::class,
                '__construct()' => [fn (Aliases $aliases) => $aliases->get('@grid-view-translation')],
            ],

            MessageFormatterInterface::class => IntlMessageFormatter::class,

            CategorySource::class => [
                'class' => CategorySource::class,
                '__construct()' => [
                    'name' => 'yii-gridview',
                ],
            ],

            TranslatorInterface::class => [
                'class' => Translator:: class,
                '__construct()' => [
                    'locale' => 'en',
                ],
                'addCategorySource()' => [Reference::to(CategorySource::class)],
            ],
        ];
    }
}
