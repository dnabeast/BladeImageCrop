<?php

return [
	'enabled' => env('BLADE_CROP_ENABLED', true),
	'cache_reset' => env('BLADE_CROP_CACHE_RESET', 12), // file check cache reset in seconds
	'disk' => 'public',
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
