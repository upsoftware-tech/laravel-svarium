<?php

namespace Upsoftware\Svarium\Panel;

use Illuminate\Http\Request;

class PanelContext
{
    public function __construct(
        public string $panel,
        public Request $request,
        public array $params = [],
    ) {}
}
