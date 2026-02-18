<?php

namespace Upsoftware\Svarium\Panel;

use Symfony\Component\HttpFoundation\Response;

abstract class Operation
{
    public static string|array $panels = 'admin';
    abstract public static function uri(): string;

    public static function methods(): array
    {
        return ['GET'];
    }

    abstract public function handle(PanelContext $context): Response;
}
