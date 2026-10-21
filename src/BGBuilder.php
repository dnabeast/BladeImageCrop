<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Cache;
use Imagick;
use ImagickDraw;
use ImagickPixel;


class BGBuilder extends BGTypeBuilder
{
	public $src;

	public function __construct($src)
	{
		$this->src = $src; // Loading and manipulating the image is expensive. Be cautious.
	}

	public function make()
	{
		return Cache::remember('bg-image-'.(floor(rand(1,6))), now()->addHour(), function () {
			$base64 = $this->fakeImage();
			return "style=\"background-size: 100% 100%; background-image:linear-gradient(#00C4FCbb, transparent 55%), url('data:image/png;base64,$base64')\"";
		});
	}

	public function fakeImage() // This isn't the faster process. Caching is important.
	{

		$newWidth = 3;
		$newHeight = 2;

		$image = new Imagick();
		$draw = new ImagickDraw();
		$pixel = new ImagickPixel('red');
		$image->newImage($newWidth, $newHeight, 'transparent', 'png');

		$subject = ['DarkKhaki','OliveDrab', 'LemonChiffon'];

		$colors = [$subject[array_rand($subject)], 'Wheat', 'Moccasin', 'Tan'];
		for ($i = 0; $i < $newWidth; $i++) {
			for ($j = 0; $j < $newHeight; $j++) {

				$pixel->setColor($colors[floor(rand(0, count($colors) - 1))]);
				$draw->setFillColor($pixel);
				$draw->point($i, $j);
			}
		}
		$image->drawImage($draw);

		$data = $image->getImage();
		$image->clear();
		return base64_encode($data);

	}

}
