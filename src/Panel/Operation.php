<?php

namespace Upsoftware\Svarium\Panel;

use Symfony\Component\HttpFoundation\Response;
use Upsoftware\Svarium\Http\ComponentResult;
use Upsoftware\Svarium\UI\Components\Flex;

abstract class Operation
{
    public static string|array $panels = 'admin';
    public static ?string $layout = null;
    public static ?string $view = 'Svarium';

    abstract public static function uri(): string;

    public static function methods(): array
    {
        return ['GET'];
    }

    public function mode(): string
    {
        return 'page';
    }

    public function rules(): array
    {
        return [];
    }

    public function authorize(PanelContext $context): bool
    {
        return true;
    }

    protected function hasSchema(): bool
    {
        return method_exists($this, 'schema');
    }

    final public function handle(PanelContext $context, ...$args): ComponentResult
    {
        if (!$this->authorize($context)) {
            abort(403);
        }

        if ($context->isPost()) {

            $schema = method_exists($this,'schema')
                ? $this->call('schema', $context, ...$args)
                : [];

            $rules = array_merge(
                $this->collectRules($schema),
                $this->rules()
            );

            $context->validate($rules);

            if (method_exists($this, 'save')) {
                $result = $this->call('save', $context, ...$args);

                if ($result) {
                    return $result;
                }

                // action bez UI
                if ($this->mode() === 'action') {
                    return response()->noContent();
                }
            }
        }

        if ($this->mode() === 'action') {
            abort(405);
        }

        return $this->render($context, ...$args);
    }

    protected function collectRules(array $schema): array
    {
        $rules = [];

        $walk = function ($components) use (&$rules, &$walk) {
            foreach ($components as $component) {

                if ($component instanceof \Upsoftware\Svarium\UI\Components\FieldComponent) {
                    $componentRules = $component->getValidationRules();

                    if (!empty($componentRules)) {
                        $rules[$component->getName()] = $componentRules;
                    }
                }

                if (!empty($component->children)) {
                    $walk($component->children);
                }

                if (!empty($component->slots)) {
                    foreach ($component->slots as $slot) {
                        $walk($slot);
                    }
                }
            }
        };

        $walk($schema);

        return $rules;
    }

    protected function render(PanelContext $context, ...$args): ComponentResult
    {
        if (!method_exists($this, 'schema')) {
            abort(204);
        }

        $schema = $this->call('schema', $context, ...$args);

        $result = new ComponentResult(
            Flex::make()->content($schema),
            static::$layout
        );

        if (method_exists($this, 'title')) {
            $title = $this->call('title', $context, ...$args);
            $result->meta('title', $title);
        }

        if (method_exists($this, 'breadcrumbs')) {
            $breadcrumbs = $this->call('breadcrumbs', $context, ...$args);
            $result->meta('breadcrumbs', $breadcrumbs);
        }

        return $result;
    }

    protected function call(string $method, PanelContext $context, ...$routeArgs)
    {
        $ref = new \ReflectionMethod($this, $method);
        $params = [];

        foreach ($ref->getParameters() as $parameter) {

            $type = $parameter->getType()?->getName();

            if ($type === PanelContext::class) {
                $params[] = $context;
                continue;
            }

            foreach ($routeArgs as $arg) {
                if ($type && $arg instanceof $type) {
                    $params[] = $arg;
                    continue 2;
                }
            }

            $params[] = $type ? app($type) : null;
        }

        return $this->$method(...$params);
    }
}
