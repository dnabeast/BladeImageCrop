<?php

return [
	'enabled' => env('BLADE_CROP_ENABLED', true),
	'disk' => 'public',
	'route_prefix' => env('BLADE_CROP_ROUTE_PREFIX', 'bic'),
	'sign_key' => env('BLADE_CROP_SIGN_KEY', env('APP_KEY')),
	'source_disk' => env('BLADE_CROP_SOURCE_DISK', 'public'),
	'public_source' => env('BLADE_CROP_PUBLIC_SOURCE', true), // also crop images found in the app's public folder (Vite builds etc.), read straight from that folder
	'cache_disk' => env('BLADE_CROP_CACHE_DISK', 'public'),
	'cache_path_prefix' => env('BLADE_CROP_CACHE_PATH_PREFIX', 'bic'), // folder on the cache disk, keeps crops apart from the originals when both share a disk
	'max_image_size' => 2000 * 2000,

	'offset_x' => 50, // percentage
	'offset_y' => 50,
	'pixel_device_ratios' => ['1x', '2x'], // add multipliers here for ultra high def screens
	'backgrounds' => true,
	'formats' => ['webp', 'jpg'],
	'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'
];
