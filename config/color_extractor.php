<?php

return [
    'accuracy' => 500, // in pixels; higher values will result in more accurate color extraction but will take longer to process
    'fallback' => '#000000', // used as a fallback for transparent images
    'temp_dir' => storage_path('color_extractor'), // images are temporarily stored here during processing
    'default_type' => 'dominant',
];
