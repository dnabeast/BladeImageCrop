<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Storage;
use League\Glide\Urls\UrlBuilder;
use League\Glide\Urls\UrlBuilderFactory;

/**
 * Turns an image src and calculated property lines into signed urls for the image route.
 * Only builds strings. Nothing here touches the disk or the network.
 */
class GlideUrls
{
	public $src;
	public $sourcePath;

	public function __construct($src)
	{
		$this->src = $src;
		$this->sourcePath = $this->sourcePath($src);
	}

	/**
	 * Path of the image on the source disk, or null when it's hosted somewhere else.
	 * "/img/photo.jpg", "img/photo.jpg" and "http://localhost/img/photo.jpg" all become "img/photo.jpg".
	 */
	public function sourcePath($src)
	{
		// A url from the source disk itself, e.g. Storage::url() on a bucket, maps back to its path on that disk
		$diskUrl = $this->sourceDiskUrl();
		if ($diskUrl && str_starts_with($src, $diskUrl . '/')) {
			return ltrim(substr($src, strlen($diskUrl)), '/');
		}

		$parts = parse_url($src);
		$appHost = parse_url(config('app.url'), PHP_URL_HOST);

		if (isset($parts['host']) && $parts['host'] !== $appHost) {
			return null;
		}

		return ltrim($parts['path'] ?? '', '/');
	}

	/**
	 * Absolute base url of the source disk, or null. Building it is string work only, even for S3, so no request is made.
	 */
	protected function sourceDiskUrl()
	{
		try {
			$url = rtrim(Storage::disk(config('bladeimagecrop.source_disk'))->url(''), '/');
		} catch (\RuntimeException $e) {
			return null; // driver can't make urls
		}

		return str_starts_with($url, 'http') ? $url : null;
	}

	public function isLocal()
	{
		return $this->sourcePath !== null;
	}

	/**
	 * A line is [width, height, offset_x, offset_y]. A height of 0 means width only, so no crop.
	 */
	public function url(array $line, $format)
	{
		$params = ['w' => $line[0]];

		if ($line[1]) {
			$params['h'] = $line[1];
			$params['fit'] = 'crop-' . $line[2] . '-' . $line[3];
		}

		$params['fm'] = $format;

		return $this->builder()->getUrl($this->sourcePath, $params);
	}

	/**
	 * One entry per line. Several sizes get width descriptors (300w), a single size repeated per pixel ratio gets 1x, 2x.
	 */
	public function srcset(array $lines, $widthDescriptors, $format)
	{
		$ratios = config('bladeimagecrop.pixel_device_ratios');

		return collect($lines)->map(function ($line, $i) use ($widthDescriptors, $ratios, $format) {
			$descriptor = $widthDescriptors ? $line[0] . 'w' : $ratios[$i];

			return $this->url($line, $format) . ' ' . $descriptor;
		})->implode(', ');
	}

	public static function mimeType($format)
	{
		return $format === 'jpg' ? 'image/jpeg' : 'image/' . $format;
	}

	protected function builder(): UrlBuilder
	{
		// Without a key Glide builds unsigned urls, which the image route would then reject
		if (!config('bladeimagecrop.sign_key')) {
			throw new \Exception('bladeimagecrop.sign_key is not set.');
		}

		return UrlBuilderFactory::create(
			url('/' . config('bladeimagecrop.route_prefix')),
			config('bladeimagecrop.sign_key')
		);
	}
}