<?php

namespace AryehRaber\ColorExtractor;

use AryehRaber\ColorExtractor\Console\Commands\ExtractColorMeta;
use Statamic\Providers\AddonServiceProvider;

class ColorExtractorServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        ExtractColorMeta::class,
    ];

    protected $modifiers = [
        ColorExtractorModifier::class,
    ];

    protected $config = [
        'color_extractor' => __DIR__.'/../config/color_extractor.php',
    ];

    public function bootAddon()
    {
        $this->publishes([
            __DIR__.'/../config/color_extractor.php' => config_path('color_extractor.php'),
        ], 'color_extractor-config');
    }
}
