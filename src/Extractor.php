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
use Statamic\Support\Arr;
use Statamic\Support\Str;

class Extractor
{
    protected ?Asset $asset = null;

    protected $img;

    protected ?string $type = null;

    public static array $strategies = ['dominant', 'average', 'contrast'];

    public function extract(Asset $asset, ?string $type = null, bool $force = false): ?string
    {
        $this->asset = $asset;
        $this->type = $type ?? config('color_extractor.default_type');

        if (! $this->asset->isImage()) {
            return null;
        }

        if (! $force && $color = Arr::get($this->asset->meta(), "data.color_{$this->type}")) {
            return $color;
        }

        try {
            $color = $this->getColor();
            $this->updateAssetMeta($color);
            return $color;
        } catch (\Exception $e) {
            \Log::error('Color Extractor Error: ' . $e->getMessage());
            return null;
        } finally {
            $this->cleanUp();
        }
    }

    public function extractAll(Asset $asset, bool $force = false): array
    {
        $results = [];

        foreach (self::$strategies as $type) {
            $existing = Arr::get($asset->meta(), "data.color_{$type}");
            
            if ($existing && ! $force) {
                $results[$type] = $existing;
                continue;
            }

            $color = $this->extract($asset, $type, $force);
            $results[$type] = $color;
            $asset = Asset::find($asset->id());
        }

        return $results;
    }

    protected function getColor(): string
    {
        $this->img = $this->processImage();

        $palette = Palette::fromFilename($this->tempImgPath());

        $extractor = new ColorExtractor($palette, Color::fromHexToInt(config('color_extractor.fallback')));

        $strategyMethod = sprintf('get%sColor', Str::title($this->type));

        return $this->{$strategyMethod}($extractor);
    }

    public function getDominantColor($extractor): string
    {
        return Color::fromIntToHex($extractor->extract(1)[0]);
    }

    public function getAverageColor($extractor): string
    {
        return Color::fromIntToHex($extractor->extract(1)[0]);
    }

    public function getContrastColor($extractor): string
    {
        $colors = collect($extractor->extract(5))->map(function ($int) {
            return Color::fromIntToHex($int);
        });

        $dominant = $colors->get(0);

        $maxContrast = 0;
        $contrastColor = $colors->get(1) ?? $dominant;

        foreach ($colors as $index => $color) {
            if ($index === 0) continue;

            $contrast = $this->calculateColorContrast($dominant, $color);

            if ($contrast > $maxContrast) {
                $maxContrast = $contrast;
                $contrastColor = $color;
            }
        }

        return $contrastColor;
    }

    protected function calculateColorContrast(string $color1, string $color2): float
    {
        $color1 = $this->normalizeHexColor($color1);
        $color2 = $this->normalizeHexColor($color2);

        if (!$color1 || !$color2) {
            return 0;
        }

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

        $l1 = $this->getRelativeLuminance($rgb1);
        $l2 = $this->getRelativeLuminance($rgb2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    protected function normalizeHexColor(string $color): ?string
    {
        $color = ltrim($color, '#');

        if (strlen($color) === 3) {
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }

        return strlen($color) === 6 ? $color : null;
    }

    protected function getRelativeLuminance(array $rgb): float
    {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    protected function processImage(): \stdClass
    {
        if (! Folder::exists($tempDir = config('color_extractor.temp_dir'))) {
            Folder::makeDirectory($tempDir);
        }

        $path = strpos($this->asset->url(), 'http') === 0
            ? $this->asset->absoluteUrl()
            : $this->asset->resolvedPath();

        $manager = new ImageManager(new Driver());
        $image = $manager->read($path);

        [$width, $height] = $this->resizeDimensions();

        if ($width && $height) {
            $image->scale($width, $height);
        } elseif ($width) {
            $image->scaleDown(width: $width);
        } elseif ($height) {
            $image->scaleDown(height: $height);
        }

        $savePath = "{$tempDir}/{$this->asset->basename()}";
        $image->save($savePath);

        return (object) [
            'dirname' => $tempDir,
            'basename' => $this->asset->basename(),
        ];
    }

    protected function tempImgPath(): ?string
    {
        if (! $this->img) {
            return null;
        }

        return "{$this->img->dirname}/{$this->img->basename}";
    }

    protected function resizeDimensions(): array
    {
        $size = $this->type === 'average' ? 2 : config('color_extractor.accuracy');

        if ($this->asset->orientation() === 'square') {
            return [$size, $size];
        }

        return [
            $this->asset->orientation() === 'landscape' ? $size : null,
            $this->asset->orientation() === 'portrait' ? $size : null,
        ];
    }

    protected function updateAssetMeta(string $color): void
    {
        $meta = $this->asset->meta();

        Arr::set($meta, "data.color_{$this->type}", $color);

        $this->asset->writeMeta($meta);

        Cache::forget($this->asset->metaCacheKey());
    }

    protected function cleanUp(): void
    {
        if ($this->tempImgPath() && File::exists($this->tempImgPath())) {
            File::delete($this->tempImgPath());
        }
    }
}
