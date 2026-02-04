<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Upsoftware\Svarium\Models\Navigation;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\multiselect;

class MenuAddCommand extends Command
{
    protected $signature = 'svarium:menu.add';

    protected $description = 'Dodaje nowe menu';

    public function handle() {
        $locales = collect(locales())
            ->pluck('label', 'value')
            ->toArray();

        $permissions = Permission::pluck('id', 'name')->toArray();
        $navigationModel = new Navigation();
        $menus = $navigationModel->getNavigationOptions();

        $routes = collect(Route::getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        $navigation = [];
        if (count($menus) >= 1) {
            $navigation['type'] = select('Rodzaj', [
                'item' => 'Pozycja menu',
                'separator' => 'Separator',
                'label' => 'Etykieta'
            ], 'item');
        }

        $count_names = 0;
        while ($count_names === 0) {
            $names = collect(multiselect('Dla jakich językow chcesz dodać menu?', $locales))
                ->mapWithKeys(fn($value) => [$value => ''])
                ->toArray();

            $count_names = count($names);
            if ($count_names === 0) {
                $this->error('Nie wybrałeś języków do dodania menu. Sprbuj ponownie');
            }
        }

        foreach($names as $key => $value) {
            $navigation["label"][$key] = text('Wprowadź nazwę ['.$locales[$key].']');
        }

        if (count($menus) <= 1) {
            Navigation::create($navigation);
            $this->info('Menu zostało dodane');
            return;
        }

        $navigation["parent_id"] = select('Wybierz nadrzędne menu', $menus);

        if ($navigation["parent_id"] === '') {
            $navigation["parent_id"] = NULL;
            Navigation::create($navigation);
            $this->info('Menu zostało dodane');
            return;
        }

        if ($navigation["type"] === "item") {
            $navigation["route_name"] = select('Wybierz route', array_merge([NULL => 'Brak', 'URL' => 'Adres URL / Path'], $routes));
            $navigation["icon"] = text('Ikonka');
            $navigation["permission"] = select("Wybierz uprawnienia", array_merge([NULL => 'Brak'], $permissions));
        }

        print_r($navigation);
        Navigation::create($navigation);
    }
}
