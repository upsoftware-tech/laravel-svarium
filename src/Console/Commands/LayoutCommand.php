<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Console\Command;
use Upsoftware\Svarium\Models\Setting;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class LayoutCommand extends Command
{
    protected $signature = 'svarium:layout';

    protected $description = '(Re)konfiguracja układu panelu';

    protected function selectColors($label = 'Wybierz kolor'): string
    {
        $tailwindColors = [
            'slate'  => 'Łupkowy (slate)',
            'gray'   => 'Szary (gray)',
            'zinc'   => 'Cynkowy (zinc)',
            'neutral' => 'Neutralny (neutral)',
            'stone'  => 'Kamienny (stone)',
            'red'    => 'Czerwony (red)',
            'orange' => 'Pomarańczowy (orange)',
            'amber'  => 'Bursztynowy (amber)',
            'yellow' => 'Żółty (yellow)',
            'lime'   => 'Limonkowy (lime)',
            'green'  => 'Zielony (green)',
            'emerald' => 'Szmaragdowy (emerald)',
            'teal'   => 'Morski (teal)',
            'cyan'   => 'Cyjan (cyan)',
            'sky'    => 'Błękitny (sky)',
            'blue'   => 'Niebieski (blue)',
            'indigo' => 'Indygo (indigo)',
            'violet' => 'Fioletowy (violet)',
            'purple' => 'Purpurowy (purple)',
            'fuchsia' => 'Fuksja (fuchsia)',
            'pink'   => 'Różowy (pink)',
            'rose'   => 'Różany (rose)',
        ];

        return select($label, $tailwindColors);
    }

    public function handle()
    {
        $layout = [];

        $layout['theme']['enabled'] = confirm('Aktywować tryb jasny i ciemny?');

        $layout['sidebar']['enabled'] = confirm('Aktywować Sidebar?');

        if ($layout['sidebar']['enabled']) {
            $layout['sidebar']['width'] = (int) text('Szerokość Sidebara (px)', 320);
            $layout['sidebar']['position'] = select(
                'Pozycja Sidebara',
                ['left' => 'Lewa strona', 'right' => 'Prawa strona'],
                'left'
            );

            $layout['sidebar']['header']['enabled'] =
                confirm('Aktywować nagłówek (header) w Sidebarze?');
        }

        $layout['header']['enabled'] =
            confirm('Aktywować nagłówek strony (Header – górny pasek)?');

        $layout['content']['header']['enabled'] =
            confirm('Aktywować nagłówek w obszarze treści (content)?');

        $layout['content']['footer']['enabled'] =
            confirm('Aktywować stopkę w obszarze treści (content)?');

        $layout['content']['appearance'] = [
            'border'  => confirm('Dodać obramowanie treści?'),
            'rounded' => confirm('Zaokrąglić rogi treści?'),
            'margin'  => confirm('Dodać marginesy wokół treści?'),
        ];

        $layout['footer']['enabled'] =
            confirm('Aktywować stopkę strony (Footer – dół strony)?');

        Setting::setSettingGlobal('layout', $layout);
    }
}
