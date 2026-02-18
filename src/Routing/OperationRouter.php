<?php

namespace Upsoftware\Svarium\Routing;

use Illuminate\Http\Request;
use Upsoftware\Svarium\Registry\OperationRegistry;

class OperationRouter
{
    public function handle(Request $request)
    {
        $path = trim($request->path(), '/');

        $operation = app(OperationRegistry::class)->resolve($path);

        if (!$operation) {
            abort(404);
        }

        return app($operation)->handle($request);
    }
}
