<?php

namespace Upsoftware\Svarium\Routing\Runtimes;

use Illuminate\Http\Request;
use Inertia\Response;
use Upsoftware\Svarium\Panel\OperationRouter;
use Upsoftware\Svarium\Routing\Area;

class PanelRuntime
{
    public function handle(Request $request, Area $area): Response
    {
        return app(OperationRouter::class)->handle(
            $request,
            $area->name,
            $area->prefix
        );
    }
}
