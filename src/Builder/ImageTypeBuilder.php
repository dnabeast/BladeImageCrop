<?php

namespace DNABeast\BladeImageCrop\Builder;

abstract class ImageTypeBuilder
{
	abstract public function resize($path);
	abstract public function save($path);
}
