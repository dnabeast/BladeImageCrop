<?php

return [
	'disk' => 'public',
	'offset_x' => 50, // percentage
	'offset_y' => 50,
	'pixel_device_ratios' => ['1x', '2x'], // add multipliers here for ultra high def screens
	'backgrounds' => true,
	'text_labels' => env('BLADE_CROP_TEST_LABELS', false), // These labels get written to the created images if they're not yet created.
	'render_source_tag_if_unavailable' => env('BLADE_CROP_RENDER_SOURCE', false),
	'build_classes' => [
		// 'avif' => 'DNABeast\BladeImageCrop\Builder\IM_AVIFBuilder',
		'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder',
		// 'webp' => 'DNABeast\BladeImageCrop\Builder\GD_WebPBuilder',
		'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
//		 'jpg' => 'DNABeast\BladeImageCrop\Builder\GD_JPGBuilder',
		// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
	],
	'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'
];
