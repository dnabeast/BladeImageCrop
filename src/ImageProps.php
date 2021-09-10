<?php

namespace DNABeast\BladeImageCrop;


class ImageProps
{

	public function calc($properties, $aspect = null){
		$this->aspect = $aspect;
		$this->offset_x = config('bladeimagecrop.offset_x');
		$this->offset_y = config('bladeimagecrop.offset_y');

		$properties = $this->properWrapping($properties);

		$properties = array_map( function($line) use ($aspect){
			return $this->calcLine($line, $aspect);
		}, $properties);

		$properties = $this->addPixelRatios($properties);

		return $properties;
	}

	public function properWrapping($properties){
		if (is_string($properties) && $properties[0] == '['){
			throw new \Exception("Properties must use : as a prefix.");
		}
		if (!is_array($properties)){
			$properties = [$properties];
		}

		if (!is_array($properties[0])){
			$properties = [$properties];
		}

		$properties = array_map(function($property){
			return is_array($property)?$property:[$property];
		}, $properties);

		return $properties;
	}

	public function calcLine($line, $aspect){
		$line[0] = (int) $line[0];

		if (!isset($line[1])){
			$line[1] =  (int) round($line[0] * $this->aspect);
		} else {
			$this->aspect = $line[1]/$line[0];
		}

		$this->offset_x = $line[2]??$this->offset_x;
		$this->offset_y = $line[3]??$this->offset_y;

		$line[2] = $this->offset_x;
		$line[3] = $this->offset_y;

		return $line;
	}

	public function addPixelRatios($properties){
		if (isset($properties[1])){
			return $properties;
		}

		return array_map( function($line) use ($properties){

			return [
				$properties[0][0] * (int) $line,
				$properties[0][1] * (int) $line,
				$properties[0][2],
				$properties[0][3],
			];
		}, config('bladeimagecrop.pixel_device_ratios') );

	}

}
