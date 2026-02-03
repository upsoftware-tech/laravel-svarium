<?php

namespace Upsoftware\Svarium\Services;

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
}
