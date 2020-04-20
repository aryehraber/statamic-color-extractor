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
    | The are two color extraction strategies; "dominant" analyses all pixels
    | in the image and calculates the most dominant color, while "average"
    | reduces the image down to 1 pixel and extracts its color.
    |
    | Supported: "dominant", "average"
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
