<?php

namespace Upsoftware\Svarium\Panel;

use Illuminate\Support\Facades\Validator;
class OperationValidator
{
    public function validate(Operation $operation, PanelContext $context, ...$args): void
    {
        $rules = $operation->validationRules($context, ...$args);

        if (!$rules) {
            return;
        }

        $attributes = $operation->validationAttributes($context, ...$args);
        $messages = $operation->validationMessages($context, ...$args);

        $validator = Validator::make(
            $context->input->all(),
            $rules,
            $messages,
            $attributes
        );

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        $context->validated = $validator->validated();
    }
}
