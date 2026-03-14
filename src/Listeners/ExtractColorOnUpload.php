<?php

namespace AryehRaber\ColorExtractor\Listeners;

use AryehRaber\ColorExtractor\Extractor;
use Exception;
use Illuminate\Support\Facades\Log;
use Statamic\Events\AssetUploaded;

class ExtractColorOnUpload
{
    public function handle(AssetUploaded $event): void
    {
        if (! config('color_extractor.auto_extract', false)) {
            return;
        }

        $asset = $event->asset;

        if (! $asset->isImage()) {
            return;
        }

        $extractor = app(Extractor::class);

        try {
            $extractor->extractAll($asset);
        } catch (Exception $e) {
            Log::error('Color Extractor Error on upload: ' . $e->getMessage());
        }
    }
}
