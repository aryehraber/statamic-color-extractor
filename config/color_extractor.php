<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Color Extraction Accuracy
    |--------------------------------------------------------------------------
    |
    | This value (in pixels) determines the width/height at which the image
    | is resized before processing. Higher values will result in more
    | accurate color extraction but will take longer to process.
    |
    */
    'accuracy' => 500,

    /*
    |--------------------------------------------------------------------------
    | Fallback Color
    |--------------------------------------------------------------------------
    |
    | Transparent images require a fallback color since it isn't possible
    | to extract from a color that has an alpha value.
    |
    */
    'fallback' => '#000000',

    /*
    |--------------------------------------------------------------------------
    | Default Color Extraction Strategy
    |--------------------------------------------------------------------------
    |
    | The are three color extraction strategies:
    |
    | - "dominant" (used by default) analyses all pixels in the image and calculates
    |   the most dominant color
    | - "contrast" will try to find a color from palette with the most contrast to
    |   the dominant color
    | - "average" reduces the image down to a tiny size and extracts its color
    |
    | Supported: "dominant", "contrast", "average"
    |
    */
    'default_type' => 'dominant',

    /*
    |--------------------------------------------------------------------------
    | Temp. Storage Directory
    |--------------------------------------------------------------------------
    |
    | During image processing, the image needs to temporarily be stored on
    | the filesystem, once done it will automatically be removed.
    |
    */
    'temp_dir' => storage_path('color_extractor'),

];
