<?php

namespace AryehRaber\ColorExtractor;

use Statamic\Assets\Asset;
use Statamic\Assets\OrderedQueryBuilder;
use Statamic\Modifiers\Modifier;
use Statamic\Support\Arr;

class ColorExtractorModifier extends Modifier
{
    protected static $handle = 'color';

    public function __construct(
        protected Extractor $extractor
    ) {}

    public function index($value, $params, $context)
    {
        $asset = $this->resolveAsset($value);

        if (! $asset instanceof Asset || ! $asset->isImage()) {
            return config('color_extractor.fallback', '#cccccc');
        }

        $type = $this->getColorType($params);

        $color = $this->extractor->extract($asset, $type);

        return $color ?? config('color_extractor.fallback', '#cccccc');
    }

    protected function resolveAsset($value): ?Asset
    {
        if ($value instanceof OrderedQueryBuilder) {
            $value = $value->first();
        }

        if ($value instanceof Asset) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return Asset::find($value);
        }

        return null;
    }

    protected function getColorType(array $params): string
    {
        $strategy = Arr::get($params, 0);

        if (in_array($strategy, Extractor::$strategies)) {
            return $strategy;
        }

        return config('color_extractor.default_type');
    }
}
