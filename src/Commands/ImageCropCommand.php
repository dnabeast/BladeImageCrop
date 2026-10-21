<?php

namespace DNABeast\BladeImageCrop\Commands;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImageCropCommand extends Command
{
	protected $signature = 'bladeimagecrop:process';

	protected $description = 'Blade Image Crop process a batch of images';

	public function handle(): void
	{
		$tasks = DB::table('bladeimagecrop_tasks')
			->limit(config('bladeimagecrop.batch_size'));

		$tasks->get()->each(function($task){
			$this->info('Running task:'.$task->id);

			$properties = collect(['originUrl', 'format', 'options', 'destinationPath'])
			->combine(json_decode($task->task_properties, true));

			(new ImageBuilder($properties['originUrl'], $properties['format']))->resize($properties['options'])->save($properties['destinationPath']);
		});

		$tasks->delete();

		$this->info('All tasks done!');
	}
}
