<?php

namespace Upsoftware\Svarium\Panel;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Upsoftware\Svarium\Http\ComponentResult;
use Upsoftware\Svarium\Http\OperationResult;

class OperationRouter
{
    protected function resolveMiddleware(array $middleware, PanelContext $context): array
    {
        return array_map(function ($middleware) use ($context) {

            return function ($request, $next) use ($middleware, $context) {

                $instance = is_string($middleware)
                    ? app($middleware)
                    : $middleware;

                if (method_exists($instance, 'handle')) {
                    return $instance->handle($request, $next, $context);
                }

                return $next($request);
            };

        }, $middleware);
    }

    public function handle(Request $request, string $panel, ?string $prefix): InertiaResponse|Response
    {
        $panelName = $panel;
        $panel = app(PanelRegistry::class)->get($panelName);

        $path = trim($request->path(), '/');

        if ($prefix) {
            $path = trim(substr($path, strlen($prefix)), '/');
        }

        $route = app(OperationRegistry::class)
            ->resolve($panelName, $request->method(), $path);

        if (!$route) {
            abort(404);
        }

        $context = new PanelContext($panelName, $request, $route['params']);
        $request->attributes->set('panel', $panelName);
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

        $middleware = array_merge(
            config('svarium.middleware.web', []),
            $panel?->getMiddleware() ?? [],
            $operation::middleware()
        );

        $result = app(\Illuminate\Pipeline\Pipeline::class)
            ->send($request)
            ->through($this->resolveMiddleware($middleware, $context))
            ->then(fn () => $operation->handle(...$args));

        if ($result instanceof ComponentResult) {

            $panelObj = $panel;
            $layout = $operation::$layout ?: $panelObj?->layout;
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
