<?php

namespace AryehRaber\ColorExtractor;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Assets\Asset;
use Statamic\Facades\File;
use Statamic\Facades\Folder;
use ColorContrast\ColorContrast;
use League\ColorExtractor\Color;
use Statamic\Modifiers\Modifier;
use League\ColorExtractor\Palette;
use Intervention\Image\Facades\Image;
use League\ColorExtractor\ColorExtractor;

class ColorExtractorModifier extends Modifier
{
    protected static $handle = 'color';

    protected $asset;

    protected $img;

    protected $type;

    protected static $strategies = ['dominant', 'average', 'contrast'];

    public function index($value, $params, $context)
    {
        if (! $this->init($value, $params)) return;

        if ($color = Arr::get($this->asset->meta(), "data.color_{$this->type}")) {
            return $color;
        }

        $color = $this->getColor();

        $this->updateAssetMeta($color);

        $this->cleanUp();

        return $color;
    }

    protected function init($value, $params)
    {
        $this->asset = Asset::find($value);

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

        $contrast = new ColorContrast();
        $contrast->addColors($colors->toArray());

        $combinations = $contrast->getCombinations(ColorContrast::MIN_CONTRAST_AA);
        $complementary = collect($combinations)
            ->filter(function ($combination) use ($dominant) {
                return in_array($dominant, [
                    '#'.$combination->getBackground(),
                ]);
            })
            ->sortByDesc(function ($combination) {
                return $combination->getContrast();
            })
            ->get(0);

        return $complementary ? '#'.$complementary->getForeground() : $colors->get(1);
    }

    protected function processImage()
    {
        if (! Folder::exists($tempDir = config('color_extractor.temp_dir'))) {
            Folder::makeDirectory($tempDir);
        }

        $image = Image::make($this->asset->resolvedPath());
        [$width, $height] = $this->resizeDimensions();

        $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });

        return $image->save("{$tempDir}/{$this->asset->basename()}");
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
    }

    protected function cleanUp()
    {
        if (File::exists($this->tempImgPath())) {
            File::delete($this->tempImgPath());
        }
    }
}
