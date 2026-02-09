<?php

namespace Upsoftware\Svarium\Resources\Pages;

use Inertia\Inertia;
use Inertia\Response;

abstract class CreatePage extends BasePage
{
    protected static ?string $pageType = 'form';
    protected static ?string $routeName = 'create';
    protected static ?string $page = null;

    public function __invoke(...$params): Response
    {
        $schemaData = static::resolveFormSchema();
        $data = [];
        $data['schema'] = $schemaData;
        $data['title'] = 'Abc';
        return Inertia::render(static::getPage(), $data);
        //return inertia(static::getPage());
    }
}
