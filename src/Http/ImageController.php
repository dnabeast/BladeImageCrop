<?php

namespace DNABeast\BladeImageCrop\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exceptions\DecoderException;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;

class ImageController
{
	public function __invoke(Request $request, string $source, string $path)
	{
		// Sign and validate against the full request path (/bic/disk/img/photo.jpg), the same string the url was signed with,
		// so the source can't be swapped without breaking the signature
		try {
			SignatureFactory::create(config('bladeimagecrop.sign_key'))
				->validateRequest($request->getPathInfo(), $request->query());
		} catch (SignatureException $e) {
			abort(403);
		}

		if ($source === 'public' && !config('bladeimagecrop.public_source')) {
			abort(404);
		}

		// Glide works with the path relative to the source (img/photo.jpg)
		try {
			$cachedPath = app('bladeimagecrop.server.' . $source)->makeImage($path, $request->query());
		} catch (FileNotFoundException $e) {
			abort(404);
		} catch (DecoderException $e) {
			abort(415);
		}

		return Storage::disk(config('bladeimagecrop.cache_disk'))->response($cachedPath, null, [
			'Cache-Control' => 'public, max-age=31536000, immutable',
		]);
	}
}
