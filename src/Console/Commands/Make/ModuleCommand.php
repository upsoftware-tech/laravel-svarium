<?php

namespace Upsoftware\Svarium\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleCommand extends Command
{
    protected $signature = 'svarium:make:module {name}';
    protected $description = 'Create new Svarium module';

    public function handle(): void
    {
        $name = Str::studly($this->argument('name'));
        $base = svarium_path("Modules/{$name}");

        if (File::exists($base)) {
            $this->error("Module {$name} already exists.");
            return;
        }

        $this->createStructure($base);
        $this->createModuleClass($name, $base);

        $this->info("Svarium module {$name} created.");
    }

    protected function createStructure(string $base): void
    {
        $dirs = [
            '',
            'Panel',
            'Web',
            'Api',
            'Forms',
            'Tables',
            'Models',
            'Policies',
        ];

        foreach ($dirs as $dir) {
            File::makeDirectory($base.'/'.$dir, 0755, true, true);
        }
    }

    protected function createModuleClass(string $name, string $base): void
    {
        $namespace = "App\\Svarium\\Modules\\{$name}";

        $content = <<<PHP
<?php

namespace {$namespace};

use Upsoftware\Svarium\Modules\Module;

class {$name}Module extends Module
{
    public function name(): string
    {
        return '{$name}';
    }
}
PHP;

        File::put($base."/{$name}Module.php", $content);
    }
}
