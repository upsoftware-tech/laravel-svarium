<?php

namespace Upsoftware\Svarium\Modules;

use Illuminate\Support\Facades\File;

class ModuleRegistry
{
    protected array $modules = [];

    public function loadFromApp(): void
    {
        $base = svarium_modules();

        if (!is_dir($base)) {
            return;
        }

        foreach (File::allFiles($base) as $file) {

            if (!str_ends_with($file->getFilename(), 'Module.php')) {
                continue;
            }

            $class = $this->classFromFile($file->getPathname());

            if (!$class || !is_subclass_of($class, Module::class)) {
                continue;
            }

            $instance = app($class);

            $instance->setPath(dirname($file->getPathname()));

            $this->register($instance);
        }
    }

    protected function classFromFile(string $path): ?string
    {
        $relative = str_replace(app_path().DIRECTORY_SEPARATOR, '', $path);
        $relative = str_replace(['/', '.php'], ['\\', ''], $relative);

        return 'App\\'.$relative;
    }

    public function register(Module $module): void
    {
        $this->modules[$module->name()] = $module;
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function registerPhase(): void
    {
        foreach ($this->modules as $module) {
            $module->register();
        }
    }

    public function bootPhase(): void
    {
        foreach ($this->modules as $module) {
            $module->boot();
        }
    }
}
