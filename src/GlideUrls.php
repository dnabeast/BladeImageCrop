<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Storage;
use League\Glide\Urls\UrlBuilder;
use League\Glide\Urls\UrlBuilderFactory;

/**
 * Turns an image src and calculated property lines into signed urls for the image route.
 * Builds strings, plus one file check when a same-host src might be in the public folder. Nothing touches the network.
 */
class GlideUrls
{
	// "disk" is the source_disk, "public" is the app's public folder. Each url carries one: /bic/disk/img/photo.jpg
	const SOURCES = ['disk', 'public'];

	public $src;
	public $source;
	public $sourcePath;

	public function __construct($src)
	{
		$this->src = $src;
		[$this->source, $this->sourcePath] = $this->locate($src);
	}

	/**
	 * [source, path] of the image, or [null, null] when it's hosted somewhere else.
	 * "/img/photo.jpg", "img/photo.jpg", "http://localhost/img/photo.jpg" and "http://first.localhost/img/photo.jpg"
	 * all become "img/photo.jpg", on the public source when that file is in the public folder and on the disk otherwise.
	 */
	public function locate($src)
	{
		// A url from the source disk itself, e.g. Storage::url() on a bucket, maps back to its path on that disk
		$diskUrl = $this->sourceDiskUrl();
		if ($diskUrl && str_starts_with($src, $diskUrl . '/')) {
			return ['disk', ltrim(substr($src, strlen($diskUrl)), '/')];
		}

		$parts = parse_url($src);

		if (isset($parts['host']) && !$this->isAppHost($parts['host'])) {
			return [null, null];
		}

		$path = ltrim($parts['path'] ?? '', '/');

		// Files built into public/ (Vite assets etc.) aren't on the source disk, so read them from the folder instead
		if (config('bladeimagecrop.public_source') && $path !== '' && is_file(public_path($path))) {
			return ['public', $path];
		}

		return ['disk', $path];
	}

	/**
	 * The app answers on its own host, on any subdomain of it, and on whatever host this request came in on.
	 * Subdomains are matched on the dot so eviltestsite.com isn't mistaken for testsite.com.
	 */
	protected function isAppHost($host)
	{
		$host = strtolower($host);
		$appHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));

		return $host === request()->getHost()
			|| ($appHost !== '' && ($host === $appHost || str_ends_with($host, '.' . $appHost)));
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

	/**
	 * Url of the original image for when the package is turned off, using the same lookup as a crop.
	 * That off switch is what you reach for when something is broken, so a broken disk leaves the src as it was instead of throwing.
	 */
	public static function original($src)
	{
		try {
			$urls = new static($src);

			if ($urls->source === 'public') {
				return url($urls->sourcePath);
			}

			if ($urls->source === 'disk' && $urls->sourcePath !== '') {
				return Storage::disk(config('bladeimagecrop.source_disk'))->url($urls->sourcePath);
			}
		} catch (\Throwable $e) {
			report($e);
		}

		return $src;
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

		return $this->builder()->getUrl($this->source . '/' . $this->sourcePath, $params);
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