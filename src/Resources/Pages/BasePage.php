<?php

namespace Upsoftware\Svarium\Resources\Pages;

use Upsoftware\Svarium\Resources\Enums\PagePath;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use ReflectionClass;

abstract class BasePage
{
    protected static ?string $resource = null;
    protected static ?string $pageType = 'table';
    protected static ?string $page = null;
    protected static ?string $method = 'get';
    protected static ?string $action = '__invoke';

    /**
     * @throws \Exception
     */
    public function __construct() {
        if (!static::$resource) {
            static::$resource = static::getResource();
        }
    }

    public static function getPage(): string
    {
        if (static::$page) {
            return static::$page;
        }

        return PagePath::from(static::$pageType)->getPagePath();
    }

    public static function getMethod(): string
    {
        return static::$method;
    }

    public static function getAction(): string
    {
        return static::$action;
    }

    public static function getRouteName(): string
    {
        return static::$routeName ?? Str::kebab(class_basename(static::class));
    }

    public static function getResource(): string
    {
        $reflection = new ReflectionClass(static::class);
        $namespace = $reflection->getNamespaceName();
        $resourceNamespace = Str::beforeLast($namespace, '\\Pages');
        $resourceName = Str::afterLast($resourceNamespace, '\\');
        $resourceClass = $resourceNamespace . "\\" . $resourceName . "Resource";
        if (!class_exists($resourceClass)) {
            throw new \Exception("Nie udało się automatycznie wykryć Resource dla strony " . static::class . ". Oczekiwano klasy: " . $resourceClass);
        }

        return $resourceClass;
    }

    protected function resolveSchema(): array
    {
        if (static::getRouteName() === 'index') {
            return $this->resolveTableSchema();
        }

        return $this->resolveFormSchema();
    }

    protected function resolveFormSchema(): array
    {
        $resource = static::getResource();
        $schemaClass = $resource::getFormSchema();
        return $schemaClass::make()->render(static::getRouteName());
    }

    protected function resolveTableSchema(): array
    {
        $resource = static::getResource();
        $schemaClass = $resource::getTableSchema();
        return $schemaClass::make()->render(static::getRouteName());
    }

    public function __invoke(...$params): Response
    {
        return Inertia::render(static::getPage());
    }
}
