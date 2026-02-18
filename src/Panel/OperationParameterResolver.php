<?php

namespace Upsoftware\Svarium\Panel;

use ReflectionMethod;

class OperationParameterResolver
{
    public function resolve(Operation $operation, PanelContext $context): array
    {
        $method = new ReflectionMethod($operation, 'handle');
        $args = [];

        foreach ($method->getParameters() as $parameter) {

            $type = $parameter->getType()?->getName();

            if ($type === PanelContext::class) {
                $args[] = $context;
                continue;
            }

            if ($type === PanelInput::class) {
                $args[] = $context->input;
                continue;
            }

            if (isset($context->params[$parameter->getName()])) {
                $args[] = $context->params[$parameter->getName()];
                continue;
            }

            $args[] = null;
        }

        return $args;
    }
}
