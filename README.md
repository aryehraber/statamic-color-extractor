# Color Extractor (Statamic 3)

**Extract colors from images.**

This addon provides a new Modifier which takes an image asset and returns its dominant (or average) color as a HEX value.

![Color Extractor example](https://user-images.githubusercontent.com/5065331/79727966-7b8e3a00-82ed-11ea-870a-8a5f4e0d05e8.jpg)

## Installation

Install the addon via composer:

```
composer require aryehraber/statamic-color-extractor
```

Publish the config file:

```
php artisan vendor:publish --provider="AryehRaber\ColorExtractor\ColorExtractorServiceProvider" --tag="config"
```

Alternately, you can manually setup the config file by creating `color_extractor.php` inside your project's config directory:

```php
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
```

## Usage

Simply add the `color` modifier to an image asset to output the HEX color value:

```html
{{ image | color }}
```

**Example**

```html
---
image: my-colorful-image.jpg
---
<div style="border-color: {{ image | color }};">
  <img src="{{ image }}">
</div>

// OR

{{ image }}
  <div style="border-color: {{ url | color }};">
    <img src="{{ glide:id }}">
  </div>
{{ /image }}
```

By default, the underlying color extractor tries to find the most dominant color in the image, however, results can vary, therefore an `average` param can be passed in to instead find the average color found in the image.

```html
{{ image | color:average }}
```

The default type can be changed to `average` instead via the config file, which opens up a `dominant` param:

```html
{{ image | color:dominant }}
```

### Editing The Color

Whenever a color is extracted from an image, it's added to the asset's meta data. This means you can manually override it by adding the following fields to your `assets.yaml` blueprint:

```yaml
title: Asset
fields:
  # existing fields
  -
    handle: color_dominant
    field:
      display: Dominant Color
      type: color
  -
    handle: color_average
    field:
      display: Average Color
      type: color
```

## Credits

https://github.com/thephpleague/color-extractor

https://github.com/sylvainjule/kirby-colorextractor
