<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Jobs;

	// Laravel
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;
	use Illuminate\Support\Facades\Cache;

	// App
	use App\Jobs\Base\BaseJob;
	use App\Events\ProjectSceneUpdated;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProcessProjectUpload extends BaseJob
{



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle(): void {

		parent::handle();

		$this->copyMappingUploads();

		$this->saveModel();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAPPING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function copyMappingUploads(): void {

		$mapping = $this->target->mapping;
		$updated = false;

		// iterate over mappings
		foreach ($mapping as &$m) {

			if($m['target'] != 'model') { continue; }
			if($m['props']['file'] == null) { continue; }
			if(!Str::contains($m['props']['file'], 'userupload/')) { continue; }
			$sourceFile = $m['props']['file'];

			// get target file
			$extension = pathinfo($sourceFile, PATHINFO_EXTENSION);
			$filename = pathinfo($sourceFile, PATHINFO_FILENAME);
			$targetFile = $this->createSlug($filename) . '.' . $extension;
			$targetFile = $this->target->storageFolder . $targetFile;

			// source file missing?
			if(Storage::disk('temp')->missing($sourceFile)) {

				// target file already exists?
				if($this->storage->exists($targetFile)) {
					$m['props']['file'] = $this->storageUrl . $targetFile;
					$updated = true;
				}
				else {
					$m['props']['file'] = null;
				}

				continue;
			}

			// copy file
			if($this->storage->exists($targetFile)) { $this->storage->delete($targetFile); }
			$this->storage->writeStream($targetFile, Storage::disk('temp')->readStream($sourceFile));
			$m['props']['file'] = $this->storageUrl . $targetFile;

			// delete source file
			Storage::disk('temp')->delete($sourceFile);

			$updated = true;
		}

		$this->target->mapping = $mapping;

		if($updated) {
			$this->target->updated_at = now();
			Cache::flush();
			ProjectSceneUpdated::dispatch($this->target);
		}
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
