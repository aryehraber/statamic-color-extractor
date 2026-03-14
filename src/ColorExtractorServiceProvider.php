<?php

namespace AryehRaber\ColorExtractor;

use AryehRaber\ColorExtractor\Console\Commands\ExtractColorMeta;
use AryehRaber\ColorExtractor\Listeners\ExtractColorOnUpload;
use Statamic\Events\AssetUploaded;
use Statamic\Providers\AddonServiceProvider;

class ColorExtractorServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        ExtractColorMeta::class,
    ];

    protected $listen = [
        AssetUploaded::class => [
            ExtractColorOnUpload::class,
        ],
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
