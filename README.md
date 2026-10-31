# Blade Image Crop

## About Blade Image Crop 5

Use WebP without the headache. Provide alternate image sizes for your user's preferred display. Reference one image on
your server then specify its dimensions in a blade component.

All the image work you're too lazy to do but with the ease of a single blade tag.

This creates the HTML required to display the correctly cropped and sized images for your use case.
It allows the browser to detect the device you are using and provides the best image.

It supports

- Automatic cropping to a specified aspect ratio and focus point
- on demand signed urls for the images (powered by Glide) 
- Multiple files for different DPI
- webp with jpeg as a fallback (In a picture tag)
- Inline fake backgrounds that display first on slow connections
- Mobile and desktop versions
- S3 and Laravel Cloud
- Support for GD library or Imagick via Intervention.

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

### Requires
PHP 8.3+
league/glide ^4.2

Laravel
- 12
- 13

## Upgrade Guide

This is a major rewrite of the package with many breaking changes.

### Copying the original image was too slow.
We no longer make a compressed version of the original image. This compression was slowing things down and was not worth the benefit.

### Folder traversal was slow
The folder structure has changed. The new images are now held in a folder called **bic** (configurable) on the public disk. You can change this disk but Laravel Cloud uses public.
The compressed folders will match the original folder structure. This allows for a sensible structure where we don't have several thousand folders in the one parent.

This change means that your compression will occur all over again. You can safely delete the blade_image_crop_holding directory.

### Faster backgrounds
The image backgrounds were poorly implemented. They were recompressing the original image after each cache clear. They are now faked. This should speed things up considerably.

### No more build classes
The system uses Glide so any custom builders you may have created will no longer work.
List your required formats in the config options 'formats' . The order indicates what is preferred in the browser.

### Image URLs have changed
Page caching and CDN caches should be cleared.

## Usage

### The easy image tag

#### Only resizing the image

If you want your image to resize to a specific width and not trim the height you may have had your image like so
```html
<img src="/storage/OverlyLargeImage.jpg" class="" alt="" />
```

Instead you would put

```blade
<x-img src="/storage/OverlyLargeImage.jpg" width="300" class="" alt="" />
```

Note: You can put the full url in the src attribute or just the local path. The output will always be the full url in
order to support other disks. Resizing only takes place on images in the allocated source disk (in your storage) and the host server allowing for Vite assets.

The initial image would be resized and saved to a 300px wide version and a 600px wide version (for hi-res displays) in
JPG format. The resulting output would be

```html
<img srcset="
https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=300&amp;fm=jpg&amp;s=signed-key 1x,
https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=600&amp;fm=jpg&amp;s=signed-key 2x"
	 style="
background-size: 100% 100%;
background-image: *random_css_gradients*"
	 src="https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=300&amp;fm=jpg&amp;s=signed-key"
	 width="300" height="200" class="" alt="">
```

The `s` value is a signature made with your `sign_key`, shown here as `signed-key`. A local path like the one above
that's found in the public folder is read from there (`/bic/public/...`). Anything else is read from the source disk
(`/bic/disk/...`).

Note that the different DPI versions are set in the srcset tag. A random mix of css gradients is placed inline into the
style tag. It also works out the height of the resized image if a height property is provided. This combination means that the loading image appears
onscreen without needing to make a HTTP request and avoids a redraw problem.
[Why this is important]( https://www.smashingmagazine.com/2020/03/setting-height-width-images-important-again/ )

The first instance of the image loading will be slow while the compressed version is created. Subsequent loads will be
much faster.

#### Resizing and cropping the image

As before you might have your image like so

```html
<img src="/storage/OverlyLargeImage.jpg" class="" alt=""/>
```

In this instance you might want your images to be 300px wide and 180px tall.

(Note the **:** before **properties** to make blade recognise it is an array and not a string)

```blade
<x-img src="/storage/OverlyLargeImage.jpg" :properties="[300, 180]" class="" alt="" />
```

This would trim the top and bottom off the image. (or if the original has a wider aspect ratio, the left and right) This
is great for if you have multiple original images with different sizes and shapes but you want your site images to be
identical.

#### Creating varying versions for different sizes and shapes

Instead of having different DPI settings you could specify the sizes (and shapes). The resulting code would no longer
use DPI multipliers and would instead set pixel width versions.

```blade
<x-img src="/storage/OverlyLargeImage.jpg" :properties="[[300, 180], [1024, 300], 2048]" class="" alt="" />
```

This creates a 300px x 180px version, a 1024px x 300px version and a 2048px x 600px version (taking the previous aspect
ratio as a guide)

The resulting code would be

```html
<img srcset="
	https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=300&amp;h=180&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key 300w,
	https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=1024&amp;h=300&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key 1024w,
	https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=2048&amp;h=600&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key 2048w"
	 style="background-size: 100% 100%; background-image: *random_css_gradients*"
	 src="https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=300&amp;h=180&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key"
	 width="300" height="180" class="" alt="">
```

**Note** that the srcset now has 300w, 1024w and 2048w instead of 1x, 2x. Also be aware that you would need to add css
styles to make sure the height (or width) was set to auto otherwise some versions of the image would be distorted. (
tailwindcss adds this by default)

#### Creating versions with specific focus points

If your original image has its point of focus not in the centre of the image (for instance a figure on the left hand
side) you can set the crop to offset horizontally.

```blade
<x-img src="https://yourserver.com/storage/OverlyLargeImage.jpg" :properties="[300, 200, 75]"/>
```

The above setting would crop to include the point 75% across the original image and 50% down.

```blade
<x-img src="https://yourserver.com/storage/OverlyLargeImage.jpg" :properties="[300, 200, 50, 25]"/>
```

This one would focus on the horizontal centre but 25% down from the top of the image.

This can be great for avatars or mobile versions of the same image.

### The pic tag

JPGs are for suckers. All the cool kids are now using WebP. Trouble is that some older browsers still can't use it. The
solution is to make multi-resolution versions of WebP and JPG (and any other format you want to use). You'd then put
your default img and your source tags inside a picture tag. Imagine if it was as easy as writing an img tag.

You don't have to. The real power of Blade Image Crop is in the pic tag.
(Note: You must wrap this tag in a picture tag)

```blade
<picture>
	<x-pic src="https://yourserver.com/storage/OverlyLargeImage.jpg" :properties="[300, 100]"/>
</picture>
```

This will result in

```html

<picture>
	<source type="image/webp" srcset="
	https://yourserver.com/bic/disk/OverlyLargeImage.jpg?w=300&amp;h=100&amp;fit=crop-50-50&amp;fm=webp&amp;s=signed-key 1x,
	https://yourserver.com/bic/disk/OverlyLargeImage.jpg?w=600&amp;h=200&amp;fit=crop-50-50&amp;fm=webp&amp;s=signed-key 2x">
	<source type="image/jpeg" srcset="
	https://yourserver.com/bic/disk/OverlyLargeImage.jpg?w=300&amp;h=100&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key 1x,
	https://yourserver.com/bic/disk/OverlyLargeImage.jpg?w=600&amp;h=200&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key 2x">
	<img style="background-size: 100% 100%; background-image: *random_css_gradients*"
		 src="https://yourserver.com/bic/disk/OverlyLargeImage.jpg?w=300&amp;h=100&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key"
		 width="300" height="100" class="" alt="">
</picture>
```

If the browser can use WebP it will. If not it will try to load the JPG.

The picture tag can take some getting used to. By default it is inline so you'll probably want to style it with a block
or inline-block. You can add classes to the picture tag or add classes to the blade component tag (and thus the img tag)
depending on which element you're targeting.

The picture tag is essential to this technique as you need multiple source tags to check for the formats.

### The sources tag

You may want more control over the source tags. If you want to add media queries or sizes information you can build the
picture in parts.

Notice the img tag needs the **sources="false"** attribute so that the srcset is placed in only the source tag rather
than both the source and the img tag.

```blade
<picture>
	<x-sources src="storage/OverlyLargeImage.jpg" :properties="[[800, 600], 1024]" sizes="(min-width: 60rem) 80vw, 100vw"/>
	<x-img sources="false" src="storage/OverlyLargeImage.jpg" :properties="[[800, 600], 1024]"/>
</picture>
```

This will output

```html

<picture>
	<source type="image/webp" srcset="
	https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=800&amp;h=600&amp;fit=crop-50-50&amp;fm=webp&amp;s=signed-key 800w,
	https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=1024&amp;h=768&amp;fit=crop-50-50&amp;fm=webp&amp;s=signed-key 1024w"
			sizes="(min-width: 60rem) 80vw, 100vw">
	<source type="image/jpeg" srcset="
	https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=800&amp;h=600&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key 800w,
	https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=1024&amp;h=768&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key 1024w"
			sizes="(min-width: 60rem) 80vw, 100vw">
	<img style="background-size: 100% 100%; background-image: *random_css_gradients*"
		 src="https://yourserver.com/bic/public/storage/OverlyLargeImage.jpg?w=800&amp;h=600&amp;fit=crop-50-50&amp;fm=jpg&amp;s=signed-key" width="800" height="600">
</picture>
```

Or, if you're looking to have a mobile version of your image that is square and a desktop version that is wide. (and
also let's pretend the focus of the image is slightly on the left )

```blade
<picture>
	<x-sources src="storage/OverlyLargeImage.jpg" :properties="[300, 300, 35, 50]" media="(max-width: 450px) and (orientation: portrait)" />
	<x-sources src="storage/OverlyLargeImage.jpg" :properties="[800, 600]" />
	<x-img sources="false" src="storage/OverlyLargeImage.jpg" :properties="[800, 600]" alt="" class=""/>
</picture>
```

This creates an extra set of source files that only activate when the media query matches.

## Config Options

### 'enabled' => env('BLADE_CROP_ENABLED', true)

If you want to turn off the package you can do so here. Tags then output the original (large) image at the same location a crop would have read it from, with the first size as its width and height attributes.
Your css still applies, and `object-fit: cover` stops the image stretching. A `<x-sources>` with a `media` attribute keeps its media query, the others output nothing.

Images on a private disk won't load this way as the browser can't reach the original. The image route stays on, so crop urls in cached pages keep working.

### 'driver' => env('BLADE_CROP_DRIVER', 'gd')

Select the image library used for the crops, either `gd` or `imagick`. The matching PHP extension needs to be installed
on the server.

Crops already in the cache aren't remade when you change this. Clear the cache disk's `cache_path_prefix` folder to
rebuild them.

### 'route_prefix' => 'bic'
This is the route prefix for where the images appear in the url.

### 'sign_key' => env('BLADE_CROP_SIGN_KEY', env('APP_KEY'))
This is the key used to sign the image urls. By default it uses the app key but if you don't want to re-sign your images when you rotate your keys add a new key here.

### 'source_disk' => env('BLADE_CROP_SOURCE_DISK', 'public')
Set the disk that the original images are stored on.

### 'public_source' => env('BLADE_CROP_PUBLIC_SOURCE', true)
This allows you to also crop images found in the app's public folder (Vite builds etc.), read straight from that folder

### 'cache_disk' => env('BLADE_CROP_CACHE_DISK', 'public')
Set the disk that the resized images are stored on. If they are the same disk you need to set a prefix.

### 'cache_path_prefix' => env('BLADE_CROP_CACHE_PATH_PREFIX', 'bic')
Set the path prefix for the resized images. If you're using a different disk as the source images you can leave this blank.

### 'max_image_size' => 2000 * 2000
This sets the maximum size in pixels of the image that can result from the crop. 

### Default Offsets

```php
'offset_x' => 50, // percentage
'offset_y' => 50,
```

If for some mad reason you wanted the default cropping to not be in the centre.

### 'pixel_device_ratios' => ['1x', '2x']

If you want to support ultra hi def screens you can change this to whatever multiplier you want. ie.['1x', '2x', '4x']

Or if you don't want any DPI options just set it to ['1x']

### 'backgrounds' => true,

The inline backgrounds can be turned off. If you need to add style tags on your image you may need to turn this off.

### 'background_colors' => ['OliveDrab','FloralWhite','DeepSkyBlue']

The theme colors for the inline backgrounds. Any css color works, including `var(--brand)`. Each gradient picks one and
offsets it slightly using css relative colors, so the browser needs to support those (Chrome 119, Safari 18, Firefox 128).

### 'formats' => ['jpg', 'webp']
The formats that the image crop will try to use. The order is the order the files will load in your browser. So if you put JPG first it won't even try to load
WebP.

### 'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'

The background builder can be over-written.
Currently it stacks a random mix of radial and linear gradients on an opaque linear gradient as a css style
background-image.

## Troubleshooting

**Is the site crashing and you're freaking out?**
Sometimes a weird file gets through. I've tried my best to get rid of potential crashes but I can never underestimate
the ingenuity of fools. If you need to turn off the process there is a config option.

```
'enabled' => env('BLADE_CROP_ENABLED', false),
```

or set it in your .env

```
BLADE_CROP_ENABLED=false
```

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
Images return and error.
```

- 404: The original image isn't on the source disk.
- 403: There's a bad signature or stale cached data.
- 415: The file isn't a proper image.

**Server failures? 500 errors? Gateway Timeouts?**
The image may be too big or too many image conversion requests are occurring simultaneously. Check your source image size and format. 

### Testing

``` bash
vendor/bin/pest
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
