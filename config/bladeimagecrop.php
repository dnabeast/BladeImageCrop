<?php

return [
	'enabled' => env('BLADE_CROP_ENABLED', true),
	'cache_reset' => env('BLADE_CROP_CACHE_RESET', 180), // file check cache reset in seconds
	'sort_by_domain_name' => env('BLADE_CROP_SORT_BY_DOMAIN_NAME', false),
	'schedule_tasks' => env('BLADE_CROP_SCHEDULE_TASKS', false),
	'batch_size' => env('BLADE_CROP_BATCH_SIZE', 8),
	'disk' => 'public',

	'route_prefix' => env('BLADE_CROP_ROUTE_PREFIX', 'bic'),
	'sign_key' => env('BLADE_CROP_SIGN_KEY', env('APP_KEY')),
	'source_disk' => env('BLADE_CROP_SOURCE_DISK', 'public'),
	'cache_disk' => env('BLADE_CROP_CACHE_DISK', 'local'),
	'max_image_size' => 2000 * 2000,

	'offset_x' => 50, // percentage
	'offset_y' => 50,
	'pixel_device_ratios' => ['1x', '2x'], // add multipliers here for ultra high def screens
	'backgrounds' => true,
	'build_classes' => [
		// 'avif' => 'DNABeast\BladeImageCrop\Builder\IM_AVIFBuilder',
		'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder',
		'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
	],
	'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'
];
