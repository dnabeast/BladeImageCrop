<?php

namespace DNABeast\BladeImageCrop;


class BGBuilder extends BGTypeBuilder
{

	public function make()
	{
		$layers = [];
		for ($i = rand(2, 4); $i > 0; $i--) {
			$layers[] = rand(0, 2) ? $this->radialLayer() : $this->linearLayer();
		}
		$layers[] = $this->baseLayer();

		return 'style="background-size: 100% 100%; background-image:' . implode(', ', $layers) . '"';
	}

	private function radialLayer()
	{
		$x = rand(0, 100);
		$y = rand(0, 100);
		$color = $this->themeColor(rand(60, 90));
		$fade = rand(40, 75);

		return "radial-gradient(circle at $x% $y%, $color, transparent $fade%)";
	}

	private function linearLayer()
	{
		$angle = rand(0, 359);
		$color = $this->themeColor(rand(50, 80));
		$fade = rand(45, 80);

		return "linear-gradient({$angle}deg, $color, transparent $fade%)";
	}

	private function baseLayer() // Opaque, the other layers fade into it
	{
		$angle = rand(0, 359);

		return "linear-gradient({$angle}deg, {$this->themeColor()}, {$this->themeColor()})";
	}

	private function themeColor($alpha = 100) // Relative color syntax, so any css color works in the config
	{
		$colors = config('bladeimagecrop.background_colors');
		$color = $colors[array_rand($colors)];
		$hue = rand(-12, 12);
		$saturation = rand(-8, 8);
		$lightness = rand(-6, 6);

		return "hsl(from $color calc(h + $hue) calc(s + $saturation) calc(l + $lightness) / $alpha%)";
	}

}
