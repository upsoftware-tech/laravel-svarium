<?php

namespace Upsoftware\Svarium\Panel;

use Illuminate\Http\Request;
use Inertia\Response;
use Upsoftware\Svarium\Http\ComponentResult;
use Upsoftware\Svarium\Http\OperationResult;

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
        $request->attributes->set('panel', $panel);
        $context->input = new PanelInput($request->all());

        $bindings = app(BindingRegistry::class);

        foreach ($context->params as $key => $value) {
            $context->params[$key] = $bindings->resolve($key, $value);
        }

        $operation = app($route['operation']);

        try {
            app(OperationAuthorizer::class)->authorize($operation, $context);
            app(OperationValidator::class)->validate($operation, $context);
        } catch (AuthorizationException $e) {
            return response('Forbidden', 403);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors], 422);
        }

        $args = app(OperationParameterResolver::class)
            ->resolve($operation, $context);

        $result = $operation->handle(...$args);

        if ($result instanceof ComponentResult) {

            $layout = $operation::$layout;
            if (!$layout) {
                $panelObj = app(PanelRegistry::class)->get($panel);
                $layout = $panelObj?->layout;
            }

            $result->setLayout($layout);
            $result->setView($operation::$view);
        }

        if ($result instanceof OperationResult) {
            return $result->toResponse();
        }

        return $result;
    }
}
