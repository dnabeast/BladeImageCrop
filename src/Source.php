<?php

namespace DNABeast\BladeImageCrop;

use DNABeast\BladeImageCrop\BladeImageCrop;
use Str;

class Source
{

	public $options;

	public function __construct($options, BladeImageCrop $bladeImageCrop )
	{
		$this->bladeImageCrop = $bladeImageCrop;
		$this->src = $options['src'];
		$this->image_format = $options['format'];
		$this->properties = $options['properties'];
		$this->media = $options['media']??'';
		$this->sizes = $options['sizes']??'';
		$this->pixelRatios = $options['pixelRatios']??false;
	}

	public static function make($options){
		return new static($options, app(BladeImageCrop::class));
	}

	public function render(){
		if( !Str::of($this->srcsetLines())->startsWith('IMAGENOTFOUND') || config('bladeimagecrop.render_source_tag_if_unavailable') ){
			return <<<EOT
			<source{$this->mediaResult()}{$this->mimeResult()} srcset="{$this->srcsetLines()}"{$this->sizesResult()}>
			EOT;
		}

	}

	public function mimeResult(){
		return $this->formatType()['mime']? ' type="'.$this->formatType()['mime'].'"' :null;
	}

	public function sizesResult(){
		return $this->sizes?' sizes="'.$this->sizes.'"':null;
	}

	public function mediaResult(){
		return $this->media?' media="'.$this->media.'"':null;
	}

	public function formatType(){
		$types = array_values( array_filter($this->mimeTypes(), function($type){
			return in_array($this->image_format, $type['extensions']);
		}) );

		if (!isset($types[0])){
			throw new \Exception("No file type found");
		}

		return $types[0];
	}

	public function srcsetLines(){
		$pixelRatios = config('bladeimagecrop.pixel_device_ratios');

		return $this->calcProperties()
			->map(function($properties, $key) use ($pixelRatios){
				$measurement = $this->pixelRatios?$pixelRatios[$key]:$properties['dimensions']['width'].'w';
				$newImageUri = $this->bladeImageCrop->fire($this->src, $properties['dimensions'], $properties['offsets'], $this->image_format);

				return $newImageUri.' '.$measurement;
			})
			->implode(",");
	}

	public function calcProperties(){

		if (is_string($this->properties)){
			throw new \Exception("Properties must use : as a prefix.");
		}

		if (!is_array($this->properties[0])) {
			$this->properties = [$this->properties];
		}

		$properties = collect( $this->properties );

		$aspect = round($properties->first()[1] / $properties->first()[0], 2);

		$initialOffset = [
			'x' => $properties->first()[2]??config('bladeimagecrop.offset_x'),
			'y' => $properties->first()[3]??config('bladeimagecrop.offset_y')
		];

		return $properties
			->map(function( $line ) use ($aspect, $initialOffset){

				if (!is_array($line)){
					$line = [$line];
				}

				return [
					'dimensions' => [
						'width' => $line[0],
						'height' => $line[1]??($line[0]*$aspect),
					],
					'offsets' => [
						'x' => $line[2]??$initialOffset['x'],
						'y' => $line[3]??$initialOffset['y'],
					]
				];

			});

	}


	protected function mimeTypes()
	{
		return [
			[ 'mime'=>'image/apng',		'extensions'=> ['apng'] ],
			[ 'mime'=>'image/avif',		'extensions'=> ['avif'] ],
			[ 'mime'=>'image/gif',		'extensions'=> ['gif'] ],
			[ 'mime'=>'image/jpeg',		'extensions'=> ['jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp'] ],
			[ 'mime'=>'image/png',		'extensions'=> ['png'] ],
			[ 'mime'=>'image/svg+xml',	'extensions'=>  ['svg'] ],
			[ 'mime'=>'image/webp',		'extensions'=> ['webp'] ]
		];
	}
}