<?php

return [
	'disk' => 'public',
	'images_from_public_path' => true,
	'offset_x' => 50, // percentage
	'offset_y' => 50,
	'pixel_device_ratios' => ['1x', '2x'], // add multipliers here for ultra high def screens
	'backgrounds' => true,
	'text_labels' => env('BLADE_CROP_TEST_LABELS', false), // These labels get written to the created images if they're not yet created.
	'build_classes' => [
		'webp' => 'DNABeast\BladeImageCrop\Builder\WebPBuilder',
		'jpg' => 'DNABeast\BladeImageCrop\Builder\JPGBuilder',
		// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
	],
	'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'
];