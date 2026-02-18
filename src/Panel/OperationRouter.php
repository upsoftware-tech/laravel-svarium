<?php

namespace Upsoftware\Svarium\Panel;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperationRouter
{
    public function handle(Request $request, string $panel, ?string $prefix): Response
    {
        $path = trim($request->path(), '/');

        if ($prefix) {
            $path = trim(substr($path, strlen($prefix)), '/');
        }

        $route = app(OperationRegistry::class)
            ->resolve($panel, $request->method(), $path);

        if (!$route) {
            abort(404);
        }

        $context = new PanelContext($panel, $request, $route['params']);

        return app($route['operation'])->handle($context);
    }
}
