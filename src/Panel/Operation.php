<?php

namespace Upsoftware\Svarium\Panel;

use Upsoftware\Svarium\Http\ComponentResult;
use Upsoftware\Svarium\Http\OperationResult;
use Upsoftware\Svarium\UI\Components\FieldComponent;
use Upsoftware\Svarium\UI\Components\Flex;

abstract class Operation
{
    public static string|array $panels = 'admin';
    public static ?string $layout = null;
    public static ?string $view = 'Svarium';
    protected static array $middleware = [];
    protected ?array $resolvedSchema = null;

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

    public static function middleware(): array
    {
        return static::$middleware ?? [];
    }

    protected function hasSchema(): bool
    {
        return method_exists($this, 'schema');
    }

    final public function handle(PanelContext $context, ...$args): OperationResult
    {
        if (!$this->authorize($context)) {
            abort(403);
        }

        if ($context->isPost()) {

            $schema = $this->getSchema($context, ...$args);

            $rules = array_merge(
                $this->collectRules($schema),
                $this->rules()
            );

            $context->validate($rules);

            if (method_exists($this, 'save')) {

                $result = $this->call('save', $context, ...$args);

                if ($result === null) {
                    return $this->render($context, ...$args);
                }

                if (!$result instanceof \Upsoftware\Svarium\Http\OperationResult) {
                    throw new \RuntimeException(
                        static::class . '::save() must return OperationResult, ' .
                        get_debug_type($result) . ' returned.'
                    );
                }

                return $result;
            }
        }

        if ($this->mode() === 'action') {
            abort(405);
        }

        return $this->render($context, ...$args);
    }

    public function validationRules(PanelContext $context, ...$args): array
    {
        $schema = $this->getSchema($context, ...$args);

        return array_merge(
            $this->collectRules($schema),
            $this->rules()
        );
    }

    protected function collectRules(array $schema): array
    {
        $rules = [];

        $walk = function ($components) use (&$rules, &$walk) {
            foreach ($components as $component) {

                if ($component instanceof FieldComponent) {
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

    protected function collectAttributes(array $schema): array
    {
        $attributes = [];

        $walk = function ($components) use (&$attributes, &$walk) {
            foreach ($components as $component) {

                if ($component instanceof FieldComponent) {

                    $name = $component->getName();
                    if (!$name) {
                        continue;
                    }

                    $attribute =
                        $component->getValidationAttribute()
                        ?? $component->getLabel()
                        ?? $name;

                    $attributes[$name] = $attribute;
                }

                foreach ($component->children ?? [] as $child) {
                    $walk([$child]);
                }

                foreach ($component->slots ?? [] as $slot) {
                    $walk($slot);
                }
            }
        };

        $walk($schema);

        return $attributes;
    }

    protected function collectMessages(array $schema): array
    {
        $messages = [];

        $walk = function ($components) use (&$messages, &$walk) {
            foreach ($components as $component) {

                if ($component instanceof FieldComponent) {

                    $name = $component->getName();
                    if (!$name) continue;

                    foreach ($component->getValidationMessages() as $rule => $text) {
                        $messages["{$name}.{$rule}"] = $text;
                    }
                }

                foreach ($component->children ?? [] as $child) {
                    $walk([$child]);
                }

                foreach ($component->slots ?? [] as $slot) {
                    $walk($slot);
                }
            }
        };

        $walk($schema);

        return $messages;
    }

    public function validationAttributes(PanelContext $context, ...$args): array
    {
        $schema = $this->getSchema($context, ...$args);

        return $this->collectAttributes($schema);
    }

    public function validationMessages(PanelContext $context, ...$args): array
    {
        $schema = $this->getSchema($context, ...$args);

        return $this->collectMessages($schema);
    }

    protected function getSchema(PanelContext $context, ...$args): array
    {
        if ($this->resolvedSchema !== null) {
            return $this->resolvedSchema;
        }

        if (!method_exists($this, 'schema')) {
            return $this->resolvedSchema = [];
        }

        $schema = $this->call('schema', $context, ...$args);

        if ($schema === null) {
            return $this->resolvedSchema = [];
        }

        return $this->resolvedSchema = is_array($schema) ? $schema : [$schema];
    }

    protected function render(PanelContext $context, ...$args): ComponentResult
    {
        if (!method_exists($this, 'schema')) {
            abort(204);
        }

        $schema = $this->getSchema($context, ...$args);

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
