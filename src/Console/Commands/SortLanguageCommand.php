<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Console\Command;
use Upsoftware\Svarium\Models\Setting;
use Upsoftware\Svarium\Traits\HasSortCommand;

class SortLanguageCommand extends Command
{
    use HasSortCommand;

    protected $signature = 'svarium:lang.sort';

    protected $description = 'Sortowanie języków';

    public function handle()
    {
        $locales = Setting::getSettingGlobal('locales');
        $locales = $this->sequentialSort($locales);

        Setting::setSettingGlobal('locales', $locales, true);
    }
}
