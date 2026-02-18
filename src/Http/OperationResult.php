<?php

namespace Upsoftware\Svarium\Http;

use Inertia\Response;

interface OperationResult
{
    public function toResponse(): Response;
}
