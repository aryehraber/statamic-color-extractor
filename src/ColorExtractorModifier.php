<?php

namespace AryehRaber\ColorExtractor;

use Illuminate\Support\Facades\Cache;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use Statamic\Assets\Asset;
use Statamic\Facades\File;
use Statamic\Facades\Folder;
use Statamic\Modifiers\Modifier;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class ColorExtractorModifier extends Modifier
{
    protected static $handle = 'color';

    protected $asset;

    protected $img;

    protected $type;

    protected static $strategies = ['dominant', 'average', 'contrast'];

    public function index($value, $params, $context)
    {
        if (! $this->init($value, $params)) {
            return config('color_extractor.fallback', '#cccccc');
        }

        if ($color = Arr::get($this->asset->meta(), "data.color_{$this->type}")) {
            return $color;
        }

        try {
            $color = $this->getColor();
            $this->updateAssetMeta($color);
            $this->cleanUp();
            return $color;
        } catch (\Exception $e) {
            \Log::error('Color Extractor Error: ' . $e->getMessage());
            $this->cleanUp();
            return config('color_extractor.fallback', '#cccccc');
        }
    }

    protected function init($value, $params)
    {
        if ($value instanceof \Statamic\Assets\OrderedQueryBuilder) {
            $value = $value->first();
        }

        if ($value instanceof Asset) {
            $this->asset = $value;
        } elseif (is_string($value) && $value !== '') {
            $this->asset = Asset::find($value);
        } else {
            $this->asset = null;
        }

        $this->type = in_array(Arr::get($params, 0), self::$strategies)
            ? Arr::get($params, 0)
            : config('color_extractor.default_type');

        if (! $this->asset instanceof Asset) {
            return false;
        }

        if (! $this->asset->isImage()) {
            return false;
        }

        return true;
    }

    protected function getColor()
    {
        $this->img = $this->processImage($this->asset);

        $palette = Palette::fromFilename($this->tempImgPath());

        $extractor = new ColorExtractor($palette, Color::fromHexToInt(config('color_extractor.fallback')));

        $strategyMethod = sprintf('get%sColor', Str::title($this->type));

        return $this->{$strategyMethod}($extractor);
    }

    public function getDominantColor($extractor)
    {
        return Color::fromIntToHex($extractor->extract(1)[0]);
    }

    public function getAverageColor($extractor)
    {
        return Color::fromIntToHex($extractor->extract(1)[0]);
    }

    public function getContrastColor($extractor)
    {
        $colors = collect($extractor->extract(5))->map(function ($int) {
            return Color::fromIntToHex($int);
        });

        $dominant = $colors->get(0);

        // Simple contrast calculation
        $maxContrast = 0;
        $contrastColor = $colors->get(1) ?? $dominant;

        foreach ($colors as $index => $color) {
            if ($index === 0) continue; // Skip dominant color

            $contrast = $this->calculateColorContrast($dominant, $color);

            if ($contrast > $maxContrast) {
                $maxContrast = $contrast;
                $contrastColor = $color;
            }
        }

        return $contrastColor;
    }

    protected function calculateColorContrast($color1, $color2)
    {
        // Strip leading # if present
        $color1 = ltrim($color1, '#');
        $color2 = ltrim($color2, '#');

        // Convert to RGB
        $rgb1 = [
            'r' => hexdec(substr($color1, 0, 2)),
            'g' => hexdec(substr($color1, 2, 2)),
            'b' => hexdec(substr($color1, 4, 2)),
        ];

        $rgb2 = [
            'r' => hexdec(substr($color2, 0, 2)),
            'g' => hexdec(substr($color2, 2, 2)),
            'b' => hexdec(substr($color2, 4, 2)),
        ];

        // Calculate relative luminance
        $l1 = $this->getRelativeLuminance($rgb1);
        $l2 = $this->getRelativeLuminance($rgb2);

        // Calculate contrast ratio
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    protected function getRelativeLuminance($rgb)
    {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    protected function processImage()
    {
        if (! Folder::exists($tempDir = config('color_extractor.temp_dir'))) {
            Folder::makeDirectory($tempDir);
        }

        $path = strpos($this->asset->url(), 'http') === 0
            ? $this->asset->absoluteUrl()
            : $this->asset->resolvedPath();

        // Intervention Image 3.x API
        $manager = new ImageManager(new Driver());
        $image = $manager->read($path);

        [$width, $height] = $this->resizeDimensions();

        // Resize using new API
        if ($width && $height) {
            $image->scale($width, $height);
        } elseif ($width) {
            $image->scaleDown(width: $width);
        } elseif ($height) {
            $image->scaleDown(height: $height);
        }

        $savePath = "{$tempDir}/{$this->asset->basename()}";
        $image->save($savePath);

        // Return object with path info
        return (object) [
            'dirname' => $tempDir,
            'basename' => $this->asset->basename(),
        ];
    }

    protected function tempImgPath()
    {
        if (! $this->img) {
            return null;
        }

        return "{$this->img->dirname}/{$this->img->basename}";
    }

    protected function resizeDimensions()
    {
        $size = $this->type === 'average' ? 2 : config('color_extractor.accuracy');

        if ($this->asset->orientation() === 'square') {
            return [$size, $size];
        }

        return [
            $this->asset->orientation() === 'landscape' ? $size : null, // width
            $this->asset->orientation() === 'portrait' ? $size : null, // height
        ];
    }

    protected function updateAssetMeta($color)
    {
        $meta = $this->asset->meta();

        Arr::set($meta, "data.color_{$this->type}", $color);

        $this->asset->writeMeta($meta);

        Cache::forget($this->asset->metaCacheKey());
    }

    protected function cleanUp()
    {
        if (File::exists($this->tempImgPath())) {
            File::delete($this->tempImgPath());
        }
    }
}
