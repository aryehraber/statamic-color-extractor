<?php

namespace AryehRaber\ColorExtractor;

use Statamic\Providers\AddonServiceProvider;

class ColorExtractorServiceProvider extends AddonServiceProvider
{
    protected $modifiers = [
        ColorExtractorModifier::class,
    ];
}
