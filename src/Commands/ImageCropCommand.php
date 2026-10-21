<?php

namespace DNABeast\BladeImageCrop\Commands;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImageCropCommand extends Command
{
	protected $signature = 'bladeimagecrop:process {task_count?}';

	protected $description = 'Blade Image Crop process a batch of images';

	public function handle(): void
	{
		$tasks = DB::table('bladeimagecrop_tasks')
			->limit($this->argument('task_count')??config('bladeimagecrop.batch_size'));

		if ($tasks->count() == 0) {
			$this->info('No tasks to process');
			return;
		}

		$tasks->get()->each(function ($task) {
			$this->info('Running task:' . $task->id);

			$properties = collect(['originUrl', 'format', 'options', 'destinationPath'])
				->combine(json_decode($task->task_properties, true));

			(new ImageBuilder($properties['originUrl'], $properties['format']))->resize($properties['options'])->save($properties['destinationPath']);
		});

		Cache::forget('bic_all_files');
		$tasks->delete();

		$this->info('All tasks done!');
	}
}
