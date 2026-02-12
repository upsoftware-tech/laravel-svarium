<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Console\Command;
use Winter\LaravelConfigWriter\ArrayFile;
use Winter\LaravelConfigWriter\EnvFile;

class CoreCommand extends Command
{
    protected $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = config('svarium.models.setting', \Upsoftware\Svarium\Models\Setting::class);
    }

    protected function addEnvKey(string $key, mixed $value, $force = false, string|bool $newLine = ''): void
    {
        $env = EnvFile::open(base_path('.env'));
        if ($newLine === true OR $newLine === 'before') {
            $env->addEmptyLine();
        }
        $env->set($key, $value);
        if ($newLine === 'after') {
            $env->addEmptyLine();
        }
        $env->write();
    }

    protected function addConfigKey(string $path, string $key, mixed $value, $force = false): void
    {
        $config = ArrayFile::open(config_path($path));
        if (strpos($value, "::")) {
            $value = $config->constant($value);
        }
        $config->set($key, $value);
        $config->write();
    }
}
