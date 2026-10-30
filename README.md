# Blade Image Crop

## About Blade Image Crop 5

Use WebP without the headache. Provide alternate image sizes for your user's preferred display. Reference one image on
your server then specify its dimensions in a blade component.

All the image work you're too lazy to do but with the ease of a single blade tag.

This crops and resizes automatically and creates multiple versions for high DPI devices and new image formats. It then
creates the HTML required to display them.

It supports

- Automatic cropping to a specified aspect ratio and focus point
- Multiple files for different DPI
- webp with jpeg as a fallback (In a picture tag)
- Inline fake backgrounds that display first on slow connections
- Mobile and desktop versions
- queuing the image processing
- S3 and Laravel Cloud

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

If you turn scheduled tasks on you'll need to publish the migration and set up the scheduler.

```bash
php artisan vendor:publish --tag=bladeimagecrop-migrations
```

```php
$schedule->command('bladeimagecrop:process')->everyMinute();
```

### Requires
Laravel
- 12
- 13

Imagick installed on server
- 7.0
- 6.1

## Upgrade Guide

This is a major rewrite of the package with many breaking changes.

### Gateway timeouts with cloud storage
Using this on Cloud created an enormous amount of Http calls. The system now caches the current list of compressed images and avoids making live calls until it needs to.
The system now automatically only gets the image via its url. If you specify a path it will convert it.

### GD library is too slow
By default we use Image Magick which (in most instances) comes installed by default on Laravel Forge and Cloud. There is still the option to switch to GD library by publishing the config file and creating a new GD library Builder.

### Copying the original image was too slow.
We no longer make a compressed version of the original image. This compression was slowing things down and was not worth the benefit. If there are not yet compressed versions the original will be used so be aware that for the first few minutes the site may use a very large original.

### Folder traversal was slow
The folder structure has changed. The original images are now held in a folder called **bic** in the public folder. You can change this folder but Laravel Cloud uses public.
The compressed folders will match the original folder structure. This allows for a sensible structure where we don't have several thousand folders in the one parent.

This change means that your compression will occur all over again. The files themselves aren't changed but the folder names have.

### Too many jobs taxed the server
This was tested on pages with dozens of images and it was creating memory problems with the dispatched jobs. The solution was to provide an option to create a list of tasks in the database. You can set the amount of tasks it runs each minute. 
The Image Magick processing is being cleared each time but something is still creating memory problems. The task solution solves this.

### More caching
To prevent having to do http calls to the server we now cache the compressed image urls. They may not appear immediately after they are processed. After five minutes they should replace the original image after which they are remembered for 24 hours.

### Faster backgrounds
The image backgrounds were poorly implemented. They were recompressing the original image after each cache clear. They are now faked and cached. This should speed things up considerably.

### Redesigned Builders
The old builders used to accept image strings. This was a bad idea. The new builders accept a path to the image. This is slightly better for memory.

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

Note: You can put the full url in the src attribute or just the local path. The output will always be the full url in
order to support other disks.

The initial image would be resized and saved to a 300px wide version and a 600px wide version (for hi-res displays) in
JPG format. The resulting output would be

```html
<img srcset="
https://yourserver.com/storage/bic/overlyLargeImage_jpg/300x200_50_50.jpg 1x,
https://yourserver.com/storage/bic/overlyLargeImage_jpg/600x400_50_50.jpg 2x"
	 style="
background-size: 100% 100%;
background-image: url('data:image/png;base64,*very_low_res_base64_encoded_img*)"
	 src="https://yourserver.com/storage/bic/overlyLargeImage_jpg/300x200_50_50.jpg"
	 width="300" height="200" class="" alt="">
```

Note that the different DPI versions are set in the srcset tag. A very low-res png image is created and converted into
base64 then placed inline into the style tag. It also works out the height of the resized image. This combination means
that the loading image appears onscreen without needing to make a html call and avoids a redraw problem.
[Why this is important]( https://www.smashingmagazine.com/2020/03/setting-height-width-images-important-again/ )

This image processing is done asynchronously so the first load will show the uncompressed image allowing time for the
various versions to be processed without slowing the page load.
If the original image continues to load on subsequent page loads double check your queue settings.

#### Resizing and cropping the image

As before you might have your image like so

```html
<img src="/storage/overlyLargeImage.jpg" class="" alt=""/>
```

In this instance you might want your images to be 300px wide and 180px tall.

(Note the **:** before **properties** to make blade recognise it is an array and not a string)

```blade
<x-img src="/storage/overlyLargeImage.jpg" :properties="[300, 180]" class="" alt="" />
```

This would trim the top and bottom off the image. (or if the original has a wider aspect ratio, the left and right) This
is great for if you have multiple original images with different sizes and shapes but you want your site images to be
identical.

#### Creating varying versions for different sizes and shapes

Instead of having different DPI settings you could specify the sizes (and shapes). The resulting code would no longer
use DPI multipliers and would instead set pixel width versions.

```blade
<x-img src="/storage/overlyLargeImage.jpg" :properties="[[300, 180], [1024, 300], 2048]" class="" alt="" />
```

This creates a 300px x 180px version, a 1024px x 300px version and a 2048px x 600px version (taking the previous aspect
ratio as a guide)

The resulting code would be

```html
<img srcset="
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/300x180_50_50.jpg 300w,
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/1024x300_50_50.jpg 1024w,
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/2048x600_50_50.jpg 2048w"
	 style="background-size: 100% 100%; background-image: url('data:image/png;base64,*very_low_res_base64_encoded_img*"
	 )
	 src="https://yourserver.com/storage/bic/overlyLargeImage_jpg/300x180_50_50.jpg"
	 width="300" height="180" class="" alt="">
```

**Note** that the srcset now has 300w, 1024w and 2048w instead of 1x, 2x. Also be aware that you would need to add css
styles to makes sure the height (or width) was set to auto otherwise some versions of the image would be distorted. (
tailwindcss adds this by default)

#### Creating versions with specific focus points

If your original image has its point of focus not in the centre of the image (for instance a figure on the left hand
side) you can set the crop to offset horizontally.

```blade
<x-img src="https://yourserver.com/storage/overlyLargeImage.jpg" :properties="[300, 200, 75]"/>
```

The above setting would crop to include the point 75% across the original image and 50% down.

```blade
<x-img src="https://yourserver.com/storage/overlyLargeImage.jpg" :properties="[300, 200, 50, 25]"/>
```

This one would focus on the horizontal centre but 25% down from the top of the image.

This can be great for avatars or mobile versions of the same image.

### The pic tag

JPGs are for suckers. All the cool kids are now using WebP. Trouble is that it's not quite fully supported yet. The
solution is to make multi-resolution versions of webp and JPG (and any other format you want to use). You'd then put
your default img and your source tags inside a picture tag. Imagine if it was as easy as writing an img tag.

You don't have to. The real power of Blade Image Crop is in the pic tag.
(Note: You must wrap this tag in a picture tag)

```blade
<picture>
	<x-pic src="https://yourserver.com/storage/overlyLargeImage.jpg" :properties="[300, 100]"/>
</picture>
```

This will result in

```html

<picture>
	<source type="image/webp" srcset="
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/300x100_50_50.webp 1x,
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/600x200_50_50.webp 2x">
	<source type="image/jpeg" srcset="
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/300x100_50_50.jpg 1x,
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/600x200_50_50.jpg 2x">
	<img style="background-size: 100% 100%; background-image: url('data:image/png;base64,*very_low_res_base64_encoded_img*')"
		 src="https://yourserver.com/storage/bic/overlyLargeImage_jpg/300x100_50_50.jpg"
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
	<x-sources src="storage/overlyLargeImage.jpg" :properties="[[800, 600], 1024]" sizes="(min-width: 60rem) 80vw, 100vw"/>
	<x-img sources="false" src="storage/overlyLargeImage.jpg" :properties="[[800, 600], 1024]"/>
</picture>
```

This will output

```html

<picture>
	<source type="image/webp" srcset="
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/800x600_50_50.webp 800w,
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/1024x768_50_50.webp 1024w"
			sizes="(min-width: 60rem) 80vw, 100vw">
	<source type="image/jpeg" srcset="
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/800x600_50_50.jpg 800w,
	https://yourserver.com/storage/bic/overlyLargeImage_jpg/1024x768_50_50.jpg 1024w"
			sizes="(min-width: 60rem) 80vw, 100vw">
	<img style="background-size: 100% 100%; background-image: url('*very_low_res_base64_encoded_img*')"
		 src="https://yourserver.com/storage/bic/overlyLargeImage_jpg/800x600_50_50.jpg" width="800" height="600">
</picture>
```

Or, if you're looking to have a mobile version of your image that is square and a desktop version that is wide. (and
also let's pretend the focus of the image in slightly on the left )

```blade
<picture>
	<x-sources src="/OverlyLargeImage_jpg" :properties="[300, 300, 35, 50]" media="(max-width: 450px) and (orientation: portrait)" />
	<x-sources src="/OverlyLargeImage_jpg" :properties="[800, 600]" />
	<x-img sources="false" src="/OverlyLargeImage.png" :properties="[800, 600]" alt="" class=""/>
</picture>
```

This creates an extra set of source files that only activate when the media query matches.

## Config Options

### 'enabled' => env('BLADE_CROP_ENABLED', true)

If you want to turn off the package you can do so here. It should still resize the original image but you may have
distortions depending on how it's used.

### 'cache_reset' => env('BLADE_CROP_CACHE_RESET', 180)

This cache is used to store the list of compressed images. Cloud based storage requires a http call to find the list of
files that have already been compressed. Caching it for a few seconds or minutes speeds up this process with little
downside.

### 'sort_by_domain_name' => env('BLADE_CROP_SORT_BY_DOMAIN_NAME', false)

If you are using multiple domains to pull your images in there is a danger that similar images could overwrite each
other. This creates a set of subfolders for each domain.

### 'schedule_tasks' => env('BLADE_CROP_SCHEDULE_TASKS', false)

Turn this on to make the package save the options to the database. Then set a scheduled job to run. This allows the
images to compress in the background if you have trouble with dispatched queues. It requires you run the attached
migration.
The command is `php artisan bladeimagecrop:process (n)` where n is the number of images to process at a time and the
default is set in the config.
The migration can be created using 'php artisan vendor:publish --tag=bladeimagecrop-migrations'

### 'batch_size' => env('BLADE_CROP_BATCH_SIZE', 8)

This is where you set the default amount. The better your server the more you can set.

### 'disk' => 'public'

Select the disk you want to use for storage.

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

### Build Classes

The default build classes can be switched out here. The keys will be the file types that are created. If you want to
replace the way the image files are compressed you can build your own. The class accepts an image url in the
constructor and needs to save that to your drive.

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

The order of keys is the order the files will load in your browser. So if you put JPG first it won't even try to load
WebP.

### 'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'

The background builder can also be over written.
Currently it creates 6 tiny fake backgrounds using a 3x2 pixel image with a linear gradient over it that is converted to
a css style background-image.

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
File not found at path: imageNotFound
```

or the image is appearing as an empty 4x3 aspect rectangle.

The original image isn't where you told it. It should be looking in the publicly available url. If you've done something wierd to
your public path this is a good place to start looking.
You can check the img's output image path to see the full path that it's looking for the image.

**Server failures? 500 errors? Gateway Timeouts?**
When loading a page of many images or some big images the php service can get overwhelmed. BladeImageCrop does the image
processing asynchronously so perhaps try setting up your queue drivers or turn on scheduled tasks.

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
