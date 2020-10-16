# Color Extractor

**Extract colors from images.**

This addon provides a new Modifier which takes an image asset and returns its dominant (or average) color as a HEX value.

![Color Extractor](https://user-images.githubusercontent.com/5065331/79727966-7b8e3a00-82ed-11ea-870a-8a5f4e0d05e8.jpg)

## Installation

Install the addon via composer:

```
composer require aryehraber/statamic-color-extractor
```

Publish the config file:

```
php artisan vendor:publish --provider="AryehRaber\ColorExtractor\ColorExtractorServiceProvider" --tag="config"
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
  <img src="{{ image }}" />
</div>

// OR {{ image }}
<div style="border-color: {{ url | color }};">
  <img src="{{ glide:id }}" />
</div>
{{ /image }}
```

By default, the underlying color extractor tries to find the most dominant color in the image, however, results can vary (see example screenshot below). Therefore an `average` param can be passed in to instead find the average color found in the image.

```html
{{ image | color:average }}
```

The default type can be changed to `average` instead via the config file, which opens up a `dominant` param:

```html
{{ image | color:dominant }}
```

The parameter `contrast` will try to find a color from the image palette with the most contrast to the dominant color:

```html
{{ image | color:contrast }}
```

### Dominant vs. Average

Example screenshot to demonstrate the difference between the two color extraction strategies:

![Color Extractor Diff](https://user-images.githubusercontent.com/5065331/79736664-75eb2100-82fa-11ea-92df-be734e426a56.jpg)

### Manually Editing Colors

Whenever a color is extracted from an image, it's added to the asset's meta data. This means you can manually override it by adding the following fields to your `assets.yaml` blueprint:

```yaml
title: Asset
fields:
  # existing fields
  - handle: color_dominant
    field:
      display: Dominant Color
      type: color
  - handle: color_average
    field:
      display: Average Color
      type: color
```

## Credits

Inspiration: https://github.com/sylvainjule/kirby-colorextractor

Color Extractor: https://github.com/thephpleague/color-extractor
