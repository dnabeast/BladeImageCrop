<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use DNABeast\BladeImageCrop\BladeImageCropServiceProvider;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Storage;
use League\Glide\Signatures\SignatureFactory;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared setup for the Glide version of the package.
 *
 * Proposed config keys (replacing build_classes, cache_reset, schedule_tasks, batch_size, sort_by_domain_name):
 * - route_prefix:   URL prefix the image route listens on, e.g. /bic/{source}/{path}
 * - sign_key:       key used to sign and validate Glide URLs
 * - source_disk:    disk the original images are read from
 * - public_source:  also read originals found in the app's public folder (Vite builds etc.)
 * - cache_disk:     disk the generated images are written to
 * - formats:        output formats in the order the browser should try them; the last one is the <img> fallback
 * - max_image_size: largest width x height Glide will produce
 */
abstract class GlideTestCase extends TestCase
{
	use InteractsWithViews;

	protected const SIGN_KEY = 'test-sign-key';

	protected $publicDir;

	protected function getPackageProviders($app)
	{
		return [BladeImageCropServiceProvider::class];
	}

	// Routes are registered when the provider boots, so config has to be in place before then
	protected function defineEnvironment($app)
	{
		$app['config']->set('app.url', 'http://localhost');
		$app['config']->set('bladeimagecrop', [
			'enabled' => true,
			'route_prefix' => 'bic',
			'sign_key' => self::SIGN_KEY,
			'source_disk' => 'bic-source',
			'public_source' => true,
			'cache_disk' => 'bic-cache',
			'cache_path_prefix' => 'bic',
			'offset_x' => 50,
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'],
			'formats' => ['webp', 'jpg'],
			'backgrounds' => false,
			'max_image_size' => 2000 * 2000,
		]);
	}

	public function setUp(): void
	{
		parent::setUp();

		Storage::fake('bic-source');
		Storage::fake('bic-cache');

		// An empty public folder per test, so same-host srcs only reach the public source when a test puts a file there
		$this->publicDir = sys_get_temp_dir() . '/bic-public-' . uniqid();
		mkdir($this->publicDir);
		$this->app->usePublicPath($this->publicDir);
	}

	public function tearDown(): void
	{
		$this->app['files']->deleteDirectory($this->publicDir);

		parent::tearDown();
	}

	/**
	 * Pull every srcset attribute out of the html, in order, and split each into [url, descriptor] pairs.
	 */
	protected function srcsets(string $html): array
	{
		preg_match_all('/srcset="([^"]*)"/', $html, $matches);

		return array_map(function ($srcset) {
			return array_map(
				fn ($entry) => preg_split('/\s+/', trim($entry)),
				preg_split('/\s*,\s*/', trim(html_entity_decode($srcset)))
			);
		}, $matches[1]);
	}

	protected function attribute(string $html, string $tag, string $attribute): ?string
	{
		preg_match('/<' . $tag . '\b[^>]*\s' . $attribute . '="([^"]*)"/', $html, $match);

		return isset($match[1]) ? html_entity_decode($match[1]) : null;
	}

	/**
	 * Check a generated url points at the image route for the right source, carries exactly the expected
	 * Glide parameters, and has a valid signature.
	 */
	protected function assertSignedImageUrl(string $url, string $expectedSourcePath, array $expectedParams, string $source = 'disk'): void
	{
		$parts = parse_url($url);
		parse_str($parts['query'] ?? '', $params);

		$this->assertEquals('localhost', $parts['host'] ?? null, "Unexpected host in $url");
		$this->assertEquals('/bic/' . $source . '/' . $expectedSourcePath, $parts['path'], "Unexpected path in $url");
		$this->assertArrayHasKey('s', $params, "Missing signature in $url");

		$withoutSignature = $params;
		unset($withoutSignature['s']);
		ksort($withoutSignature);
		ksort($expectedParams);
		$this->assertEquals(array_map('strval', $expectedParams), $withoutSignature, "Unexpected parameters in $url");

		// Throws SignatureException if the signature doesn't match
		SignatureFactory::create(self::SIGN_KEY)->validateRequest($parts['path'], $params);
	}

	protected function signedUrl(string $sourcePath, array $params, string $source = 'disk'): string
	{
		$path = '/bic/' . $source . '/' . $sourcePath;
		$params = SignatureFactory::create(self::SIGN_KEY)->addSignature($path, $params);

		return $path . '?' . http_build_query($params);
	}

	protected function responseBody($response): string
	{
		$base = $response->baseResponse;

		if ($base instanceof StreamedResponse) {
			return $response->streamedContent();
		}
		if ($base instanceof BinaryFileResponse) {
			return file_get_contents($base->getFile()->getPathname());
		}

		return $base->getContent();
	}

	/**
	 * Put a generated image on the source disk, or in the public folder. Left half red, right half blue, so crops can be checked by colour.
	 */
	protected function putSplitImage(string $path, int $width = 800, int $height = 600, string $source = 'disk'): void
	{
		$image = imagecreatetruecolor($width, $height);
		imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, $height - 1, imagecolorallocate($image, 255, 0, 0));
		imagefilledrectangle($image, intdiv($width, 2), 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 0, 255));

		ob_start();
		str_ends_with($path, '.png') ? imagepng($image) : imagejpeg($image, null, 95);
		$data = ob_get_clean();

		if ($source === 'public') {
			$this->app['files']->ensureDirectoryExists(dirname(public_path($path)));
			file_put_contents(public_path($path), $data);
			return;
		}

		Storage::disk('bic-source')->put($path, $data);
	}

	protected function centreColour(string $imageData): array
	{
		$image = imagecreatefromstring($imageData);
		$rgb = imagecolorat($image, intdiv(imagesx($image), 2), intdiv(imagesy($image), 2));

		return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
	}
}
