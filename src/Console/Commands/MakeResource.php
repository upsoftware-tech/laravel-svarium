<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use Illuminate\Support\Str;
use File;

class MakeResource extends Command
{
    protected $signature = 'svarium:make.resource {resource?}';
    protected $description = 'Create Svarium Resource';

    protected string $resourceName;

    public function replaceStub($content): string
    {
        $replace['{{ResourceName}}'] = Str::studly($this->resourceName);
        $replace['{{resource-name}}'] = Str::slug($this->resourceName);
        $replace['{{ResourceNamePlural}}'] = Str::plural($replace['{{ResourceName}}'] );

        return strtr($content, $replace);
    }

    public function handle() {
        $resource = $this->argument('resource');
        while(!$resource || strlen($resource) < 3) {
            $resource = text(__('Set name resource (min. 3 characters)', __('E.g. Pages')));
        }
        svarium_resources();
        if (!is_dir(svarium_resources())) {
            File::makeDirectory(svarium_resources(), 0755, true);
        }

        $this->resourceName = $resource;

        $resourceDir = svarium_resources($this->resourceName);
        $resourceDirPages = $resourceDir . DIRECTORY_SEPARATOR . 'Pages';
        $resourceDirSchemas = $resourceDir . DIRECTORY_SEPARATOR . 'Schemas';
        $resourceFile = $resourceDir . DIRECTORY_SEPARATOR . $resource.'Resource.php';
        $resourcePageCreate = $resourceDirPages . DIRECTORY_SEPARATOR . 'Create'.$this->resourceName.'.php';
        $resourceSchemaTable = $resourceDirSchemas . DIRECTORY_SEPARATOR . $this->resourceName.'Table.php';
        $resourceSchemaForm = $resourceDirSchemas . DIRECTORY_SEPARATOR . $this->resourceName.'Form.php';
        $stubsDir = __DIR__ . '/../..'. DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR;

        if (!File::isDirectory($resourceDir)) {
            File::makeDirectory($resourceDir);
            File::makeDirectory($resourceDirPages);
            File::makeDirectory($resourceDirSchemas);
        } else {
            $this->warn(__('Directory already exists!'));
            $delete = confirm(__('Are you sure you want to delete it and create new resource?'), false, __('Yes'), __('No'));
            if ($delete) {
                if (File::deleteDirectory($resourceDir)) {
                    $this->info(__('Old directory deleted successfully.'));
                    File::makeDirectory($resourceDir, 0755, true);
                    File::makeDirectory($resourceDirPages);
                    File::makeDirectory($resourceDirSchemas);
                } else {
                    $this->error(__('Could not delete the directory. Check permissions.'));
                    return;
                }
                $this->newLine();
            } else {
                return;
            }
        }

        // STUBS
        $resourceStubFile = File::get($stubsDir.'svarium.resource.php.stub');
        $resourcePageCreateStubFile = File::get($stubsDir.'page.create.php.stub');
        $resourceSchemaTableStubFile = File::get($stubsDir.'svarium.schema.table.php.stub');
        $resourceSchemaFormStubFile = File::get($stubsDir.'svarium.schema.form.php.stub');

        File::put($resourceFile, $this->replaceStub($resourceStubFile));
        File::put($resourcePageCreate, $this->replaceStub($resourcePageCreateStubFile));
        File::put($resourceSchemaTable, $this->replaceStub($resourceSchemaTableStubFile));
        File::put($resourceSchemaForm, $this->replaceStub($resourceSchemaFormStubFile));
        $this->info(__('Created new resource successfully.'));
        $this->line(__('Resource Path: :file', ['file' => $resourceFile]));
        $this->line(__('Page Create: :file', ['file' => $resourcePageCreate]));
        $this->line(__('Schema table: :file', ['file' => $resourceSchemaTable]));
        $this->line(__('Schema form: :file', ['file' => $resourceSchemaForm]));
    }
}
