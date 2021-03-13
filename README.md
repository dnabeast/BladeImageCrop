# Use Blade to resize and crop images.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dnabeast/bladeimagecrop.svg?style=flat-square)](https://packagist.org/packages/dnabeast/bladeimagecrop)
[![Build Status](https://img.shields.io/travis/dnabeast/bladeimagecrop/master.svg?style=flat-square)](https://travis-ci.org/dnabeast/bladeimagecrop)
[![Quality Score](https://img.shields.io/scrutinizer/g/dnabeast/bladeimagecrop.svg?style=flat-square)](https://scrutinizer-ci.com/g/dnabeast/bladeimagecrop)
[![Total Downloads](https://img.shields.io/packagist/dt/dnabeast/bladeimagecrop.svg?style=flat-square)](https://packagist.org/packages/dnabeast/bladeimagecrop)

Creates a blade component that grabs an image and resizes it and crops it to match the blade settings.

## Installation

You can install the package via composer:

```bash
composer require dnabeast/bladeimagecrop
php artisan view:clear

```

## Usage

In your blade file where the image tag says something like
```
<img src="/img/overlyLargeImage.jpg" alt="" />
```
instead put
```
<img src="@image('/img/overlyLargeImage.jpg', [400, 300])" alt="" />
```

This will result in a copy of the image with the dimensions 400px x 300px being written to
```
/img/overlyLargeImage_jpg/400x300_50_50.jpg
```

and the image tag reflecting the new file
```
<img src="/img/overlyLargeImage_jpg/400x300_50_50.jpg" alt="" />
```

if you want to select the center of the image you can do so using a percentage value like so where the final image
will focus on 25% across and 75% down. It will use the distance to the closest edge to compute the final image scale.

```
@image('/img/overlyLargeImage.jpg', [400, 300], [25, 75])
```

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