<?php

namespace AryehRaber\ColorExtractor;

use Statamic\Providers\AddonServiceProvider;

class ColorExtractorServiceProvider extends AddonServiceProvider
{
    protected $modifiers = [
        ColorExtractorModifier::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom(__DIR__.'/../config/color_extractor.php', 'color_extractor');

        $this->publishes([
            __DIR__.'/../config/color_extractor.php' => config_path('color_extractor.php'),
        ], 'config');
    }
}
