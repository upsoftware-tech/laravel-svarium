<?php

namespace Upsoftware\Svarium\Services;

class LayoutService
{
    protected string $title = '';

    public function set_title($title): void
    {
        $this->title = $title;
    }
}
