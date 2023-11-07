# Blade Image Crop


## About Blade Image Crop 3

Use WebP without the headache. Provide alternate image sizes for your user's preferred display. Reference one image on your server then specify its dimensions in a blade component.

All the image work you're too lazy to do but with the ease of a single blade tag.

This crops and resizes automatically and creates multiple versions for high DPI devices and new image formats. It then creates the HTML required to display them.

It supports
- Automatic cropping to a specified aspect ratio and focus point
- Multiple files for different DPI
- webp with jpeg as a fallback (In a picture tag)
- Inline backgrounds that display first on slow connections
- Mobile and desktop versions
- local AND online images
- queuing the image processing

## Installation

You can install the package via composer:

```bash
composer require dnabeast/bladeimagecrop
```

You can publish the config file
```bash
php artisan vendor:publish --provider="DNABeast\BladeImageCrop\BladeImageCropServiceProvider"
```

By default the system is set to use the Laravel public storage path so don't forget to set up
```bash
php artisan storage:link
```

Or make whatever changes you wish to the storage. (more info below)

## Upgrade Guide

Because it uses the Laravel Http helper this program no longer supports Laravel 6.
By default we use Image Magick which comes installed by default on Laravel Forge. There is still the option to switch to GD library by publishing the config file and selecting the GD options.
The system now grabs the original src from the public directory OR an URL (rather than the weird workaround needed by the FileStorage system )
The next time you load an image using the blade component it will duplicate your image to a holding directory and remake your resized images. It will only do this once.
You can safely remove older versions of files.
If you've turned the images_from_public_path to false then it's not going to work any more and you'll have to update your img src attributes.
No longer upscales images if the original image is too small. (It allows the browser to do this)

## Usage

### The easy image tag

#### Only resizing the image

If you want your image to resize to a specific width and not trim the height you may have had your image like so
```html
<img src="/storage/overlyLargeImage.jpg" class="" alt="" />
```

Instead you would put
```blade
<x-img src="/storage/overlyLargeImage.jpg" width="300" class="" alt="" />
```

The initial image would be resized and saved to a 300px wide version and a 600px wide version (for hi-res displays) in JPG format. The resulting output would be
```html
<img srcset="
/storage/blade_image_crop_holding/overlyLargeImage_jpg/300x200_50_50.jpg 1x,
/storage/blade_image_crop_holding/overlyLargeImage_jpg/600x400_50_50.jpg 2x"
style="
background-size: 100% 100%;
background-image: url('data:image/png;base64,*very_low_res_base64_encoded_img*")"
src="/storage/blade_image_crop_holding/overlyLargeImage_jpg/320x240_50_50.jpg"
width="300" height="200" class="" alt="">
```

Note that the different DPI versions are set in the srcset tag. A very low-res png image is created and converted into base64 then placed inline into the style tag. It also works out the height of the resized image. This combination means that the loading image appears onscreen without needing to make a html call and avoids a redraw problem.
[Why this is important]( https://www.smashingmagazine.com/2020/03/setting-height-width-images-important-again/ )

This image processing is done asynchronously so the first load will show the uncompressed image allowing time for the various versions to be processed without slowing the page load.
If the original image continues to load on subsequent page loads double check your queue settings. 

#### Resizing and cropping the image

As before you might have your image like so
```html
<img src="/storage/overlyLargeImage.jpg" class="" alt="" />
```

In this instance you might want your images to be 300px wide and 180px tall.

(Note the **:** before **properties** to make blade recogise it is an array and not a string)
```blade
<x-img src="/storage/overlyLargeImage.jpg" :properties="[300, 180]" class="" alt="" />
```

This would trim the top and bottom off the image. (or if the original has a wider aspect ratio, the left and right) This is great for if you have multiple original images with different sizes and shapes but you want your site images to be identical.

#### Creating varying versions for different sizes and shapes
Instead of having different DPI settings you could specify the sizes (and shapes). The resulting code would no longer use DPI multipliers and would instead set pixel width versions.
```blade
<x-img src="/storage/overlyLargeImage.jpg" :properties="[[300, 180], [1024, 300], 2048]" class="" alt="" />
```
This creates a 300px x 180px version, a 1024px x 300px version and a 2048px x 600px version (taking the previous aspect ratio as a guide)

The resulting code would be
```html
<img srcset="
	/storage/blade_image_crop_holding/overlyLargeImage_jpg/300x180_50_50.jpg 300w,
	/storage/blade_image_crop_holding/overlyLargeImage_jpg/1024x300_50_50.jpg 1024w,
	/storage/blade_image_crop_holding/overlyLargeImage_jpg/2048x600_50_50.jpg 2048w"
style="background-size: 100% 100%; background-image: url('data:image/png;base64,*very_low_res_base64_encoded_img*")
	 src="/storage/blade_image_crop_holding/overlyLargeImage_jpg/300x180_50_50.jpg"
	 width="300" height="180" class="" alt="">
```

**Note** that the srcset now has 300w, 1024w and 2048w instead of 1x, 2x. Also be aware that you would need to add css styles to makes sure the height (or width) was set to auto otherwise some versions of the image would be distorted. (tailwindcss adds this by default)


#### Creating versions with specific focus points

If your original image has its point of focus not in the centre of the image (for instance a figure on the left hand side) you can set the crop to offset horizontally.

```blade
<x-img src="/storage/overlyLargeImage.jpg" :properties="[300, 200, 75]"/>
```

The above setting would crop to include the point 75% across the original image and 50% down.

```blade
<x-img src="/storage/overlyLargeImage.jpg" :properties="[300, 200, 50, 25]"/>
```
This one would focus on the horizontal centre but 25% down from the top of the image.

This can be great for avatars or mobile versions of the same image.


### The pic tag

JPGs are for suckers. All the cool kids are now using WebP. Trouble is that it's not quite fully supported yet. The solution is to make multi-resolution versions of webp and JPG (and any other format you want to use). You'd then put your default img and your source tags inside a picture tag. Imagine if it was as easy as writing an img tag.

You don't have to. The real power of Blade Image Crop is in the pic tag.
(Note: You must wrap this tag in a picture tag)
```blade
<picture>
	<x-pic src="/storage/overlyLargeImage.jpg" :properties="[300, 100]"/>
</picture>
```

This will result in
```html
<picture>
	<source type="image/webp" srcset="
	/storage/blade_image_crop_holding/overlyLargeImage_jpg/300x100_50_50.webp 1x,
	/storage/blade_image_crop_holding/overlyLargeImage_jpg/600x200_50_50.webp 2x">
	<source type="image/jpeg" srcset="
	/storage/blade_image_crop_holding/overlyLargeImage_jpg/300x100_50_50.jpg 1x,
	/storage/blade_image_crop_holding/overlyLargeImage_jpg/600x200_50_50.jpg 2x">
	<img  style="background-size: 100% 100%; background-image: url('data:image/png;base64,*very_low_res_base64_encoded_img*"')"
	src="/storage/blade_image_crop_holding/overlyLargeImage_jpg/300x100_50_50.jpg"
	width="300" height="100" class="" alt="">
</picture>
```

If the browser can use WebP it will. If not it will try to load the JPG.

The picture tag can take some getting used to. By default it is inline so you'll probably want to style it with a block or inline-block. You can add classes to the picture tag or add classes to the blade component tag (and thus the img tag) depending on which element you're targeting.

The picture tag in essential to this technique as you need multiple source tags to check for the formats.

### The sources tag

You may want more control over the source tags. If you want to add media queries or sizes information you can build the picture in parts.

Notice the img tag needs the **sources="false"** attribute so that the srcset is placed in only the source tag rather than both the source and the img tag.

```blade
<picture>
	<x-sources src="/storage/blade_image_crop_holding/overlyLargeImage_jpg" :properties="[[800, 600], 1024]" sizes="(min-width: 60rem) 80vw, 100vw"/>
	<x-img sources="false" src="/img/OverlyLargeImage.png" :properties="[[800, 600], 1024]"/>
</picture>
```
This will output
```html
<picture>
	<source type="image/webp" srcset="
	/cater_jpg/800x600_50_50.webp 800w,
	/cater_jpg/1024x768_50_50.webp 1024w"
	sizes="(min-width: 60rem) 80vw, 100vw">
	<source type="image/jpeg" srcset="
	/cater_jpg/800x600_50_50.jpg 800w,
	/cater_jpg/1024x768_50_50.jpg 1024w"
	sizes="(min-width: 60rem) 80vw, 100vw">
	<img style="background-size: 100% 100%; background-image: url('*very_low_res_base64_encoded_img*')" src="/cater_jpg/800x600_50_50.jpg" width="800" height="600" >
</picture>
```

Or, if you're looking to have a mobile version of your image that is square and a desktop version that is wide. (and also let's pretend the focus of the image in slightly on the left )
```blade
<picture>
	<x-sources src="/storage/blade_image_crop_holding/overlyLargeImage_jpg" :properties="[300, 300, 35, 50]" media="(max-width: 450px) and (orientation: portrait)" />
	<x-sources src="/storage/blade_image_crop_holding/overlyLargeImage_jpg" :properties="[800, 600]" />
	<x-img sources="false" src="/img/OverlyLargeImage.png" :properties="[800, 600]" alt="" class=""/>
</picture>
```
This creates an extra set of source files that only activate when the media query matches.


## Config Options

### 'disk' => 'public'

Select the disk you want to use for storage

### Default Offsets
```php
'offset_x' => 50, // percentage
'offset_y' => 50,
```

If for some mad reason you wanted the default cropping to not be in the centre.

### 'pixel_device_ratios' => ['1x', '2x']
If you want to support ultra hi def screens you can change this to whatever multipler you want. ie.['1x', '2x', '4x']

Or if you don't want any DPI options just set it to ['1x']

### 'backgrounds' => true,

The inline backgrounds can be turned off. If you need to add style tags on your image you may need to turn this off.

### 'text_labels' => env('BLADE_CROP_TEST_LABELS', false),

If you need to test to make sure the correct image is being displayed turning this to true will write the filename onto the image itself.
**Beware:** Any files created with this flag on will keep this label once you turn it off again. The images should be deleted so that they can be recreated.

### 'compress_held_image' => env('BLADE_CROP_COMPRESS_HELD_IMAGE', true)

The package creates and stores a Held Image that it uses for reference. If the original image vanishes for some reason there is always a source of truth. If this image is not compressed at all we can ensure that it's at least stored as slightly lossy else your storage fills up with lossless images.

## 	'render_source_tag_if_unavailable' => env('BLADE_CROP_RENDER_SOURCE', false),

When developing if can be useful to see the source tags even when an error has occurred. In production you can turn this off. If you rely on JS to detect image errors turn this off.
ie.
```
<img src="broken_img.jpg" onerror="this.onerror=null; this.src='/svg/backup.svg'"/>
```

### Build Classes
The default build classes can be switched out here. The keys will be the file types that are created. If you want to replace the way the image files are compressed you can build your own. The class accepts an image string in the constructor and needs to save that to your drive.

```php
'build_classes' => [
	//'avif' => 'DNABeast\BladeImageCrop\Builder\IM_AVIFBuilder',
	'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder',
	// 'webp' => 'DNABeast\BladeImageCrop\Builder\GD_WebPBuilder',
	'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
	// 'jpg' => 'DNABeast\BladeImageCrop\Builder\GD_JPGBuilder',
	// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
],
```

The order of keys is the order the files will load in your browser. So if you put JPG first it won't even try to load WebP.

### 'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'
The background builder can also be over written. Currently it takes the images and resizes it to 4px x 4px. It then converts that to base64 and return the style tag with the background info. This is cached by Laravel.

If you wanted to (for instance) change this to load the same loading image you can write your own builder and swap it in with the config.

## Troubleshooting
**Are you getting this error?**
```
syntax error, unexpected end of file, expecting "elseif" or "else" or "endif"
```
It probably means you haven't closed the blade component tag. Use one of these solutions.
```blade
<x-img />
<x-img></x-img>
```

**How about this one?**
```
File not found at path: imageNotFound
```
or the image is appearing as an empty 4x3 aspect rectangle.

The original image isn't where you told it. It should be looking in the public path. If you've done something weird to your public path this is a good place to start looking.
In your local environment you can check the img's output image path to see the full path that it's looking for the image.

**Server failures? 500 errors?**

When loading a page of many images or some big images the php service can get overwhelmed. BladeImageCrop does the image processing asynchronously so perhaps try setting up your queue drivers.

### Testing

``` bash
vendor/bin/phpunit
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email dan@civicnet.com.au instead of using the issue tracker.

## Credits

- [Dan Beeston](https://github.com/dnabeast)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
