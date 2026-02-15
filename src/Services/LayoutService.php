<?php

namespace Upsoftware\Svarium\Services;

use Illuminate\Support\Facades\Route;
use Upsoftware\Svarium\Resources\Pages\BasePage;

class LayoutService
{
    protected string $title = '';
    protected array $breadcrumbs = [];

    public function set_title(string $title): void
    {
        $this->title = $title;
    }

    public function set_breadcrumb(array $breadcrumb): static
    {
        $this->breadcrumbs[] = $breadcrumb;
        return $this;
    }

    public function set_breadcrumbs(array $breadcrumbs): void
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    public function get_title(): string
    {
        return $this->title;
    }

    public function getComponents() {
        return [
            'header' => [],
            'sidebar' => [],
            'content' => [],
            'footer' => [],
        ];
    }

    public function component(string $component) : mixed
    {
        $pageClass = Route::current()->getControllerClass();
        $basePageClass = BasePage::class;

        if (is_subclass_of($pageClass, $basePageClass)) {
            if (!class_exists($pageClass)) {
                throw new \Exception("Component class not found: $pageClass");
            } else {
                if ($pageClass::${$component} !== null) {
                    return new $pageClass::${$component};
                }
            }
        }
        return false;
    }
}
