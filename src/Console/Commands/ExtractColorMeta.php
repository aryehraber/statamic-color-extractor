<?php

namespace AryehRaber\ColorExtractor\Console\Commands;

use AryehRaber\ColorExtractor\Extractor;
use Illuminate\Console\Command;
use Statamic\Facades\AssetContainer;

class ExtractColorMeta extends Command
{
    protected $signature = 'color-extractor:extract
                            {--container= : Filter by container handle}
                            {--folder= : Filter by folder path}
                            {--all : Extract all color types (dominant, average, contrast)}
                            {--force : Force re-extraction even if colors already exist}';

    protected $description = 'Extract color data from assets and save to meta';

    public function __construct(
        protected Extractor $extractor
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $containerHandle = $this->option('container');
        $folderPath = $this->option('folder');
        $extractAll = $this->option('all');
        $force = $this->option('force');

        $containers = $containerHandle
            ? [$containerHandle => AssetContainer::find($containerHandle)]
            : AssetContainer::all();

        $containers = collect($containers)->filter();

        if ($containers->isEmpty()) {
            $this->error('No asset containers found.');
            return self::FAILURE;
        }

        $assets = collect();
        foreach ($containers as $container) {
            $query = $container->queryAssets();

            if ($folderPath) {
                $query->where('folder', $folderPath);
            }

            foreach ($query->get() as $asset) {
                if ($asset->isImage()) {
                    $assets->push($asset);
                }
            }
        }

        if ($assets->isEmpty()) {
            $this->info('No image assets found.');
            return self::SUCCESS;
        }

        $this->info("Found {$assets->count()} image asset(s).");

        $progressBar = $this->output->createProgressBar($assets->count());
        $progressBar->start();

        $processed = 0;
        $errors = 0;

        foreach ($assets as $asset) {
            try {
                if ($extractAll) {
                    $this->extractor->extractAll($asset, $force);
                } else {
                    $this->extractor->extract($asset, null, $force);
                }
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->warn("  Error processing [{$asset->path()}]: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Processed: {$processed}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
