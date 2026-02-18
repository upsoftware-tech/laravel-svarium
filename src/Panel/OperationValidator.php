<?php

namespace Upsoftware\Svarium\Panel;

use Illuminate\Support\Facades\Validator;
class OperationValidator
{
    public function validate(Operation $operation, PanelContext $context): void
    {
        $rules = $operation->rules();

        if (!$rules) {
            return;
        }

        $validator = Validator::make(
            $context->input->all(),
            $rules
        );

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }
    }
}
