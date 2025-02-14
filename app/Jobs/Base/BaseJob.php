<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Jobs\Base;

	// Laravel
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Support\Str;
	use Intervention\Image\Laravel\Facades\Image as Image;
	use Tinify\Source;
	use Tinify\Tinify;
	use Throwable;
	use Cocur\Slugify\Slugify;

	// App
	use App\Models\BaseModel;
	use App\Traits\LangKeysTrait;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseJob implements ShouldQueue {

	// Traits
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LangKeysTrait;

	protected $target;
	protected $tinyPng = null;

	protected $storage = null;
	protected $storageUrl = null;

	// every available language key with a prefix "_" for dynamic model properties
	protected $langKeys = [];


	public function __construct(BaseModel $target) {

		$this->target = $target->withoutRelations();
		$this->langKeys = $this->getLangKeys();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	JOB
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function handle(): void {

		$this->storage = Storage::disk($this->target->storageDisk ?? config('filesystems.default'));
		$this->storageUrl = $this->storage->url('/');
	}


	public function failed(Throwable $exception): void {

		$name = $this->target->name ?? 'unknown';
		Log::critical("Job failed: " . get_class($this) . ". Model name: " . $name  . ". exception: " . $exception->getMessage());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MODEL HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function saveModel(): void {

		if(isset($this->target->processed)) { $this->target->processed = true; }

		$this->target->save();

		Cache::flush();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	IMAGE PROCESSING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function convertJpeg(string $property, int $width, int $height, string $targetName, bool $overwriteProp=true, int $qualityJpg=90, int $qualityWebp=75): bool|string {

		// image processing
		$imgUrl = $this->convertImage('jpg', $width, $height, $this->target[$property], $targetName, $qualityJpg, $qualityWebp);

		// save updated property in target model
		if($imgUrl && $overwriteProp) {
			$this->deleteUploadFile($this->target[$property]);
			$this->target[$property] = $imgUrl;
		}

		return $imgUrl;
	}


	public function convertPng(string $property, int $width, int $height, string $targetName, bool $overwriteProp=true, int $qualityJpg=90, int $qualityWebp=75): bool|string {

		// image processing
		$imgUrl = $this->convertImage('png', $width, $height, $this->target[$property], $targetName, $qualityJpg, $qualityWebp);

		// save updated property in target model
		if($imgUrl && $overwriteProp) {
			$this->deleteUploadFile($this->target[$property]);
			$this->target[$property] = $imgUrl;
		}

		return $imgUrl;
	}


	protected function convertImage(string $imageType, int $width, $height, $uploadFile, string $targetName, int $qualityJpg=90, int $qualityWebp=75): bool|string {

		// define new image path
		$targetName = Str::replace('_','-',$targetName);
		$imgName = $this->target->storageFolder . $targetName . '.' . $imageType;

		// validate upload
		$validate = $this->validateUpload($uploadFile, $imgName);
		if($validate !== true) { return $validate; }

		// add storage url to target
		$path = Str::start($uploadFile, $this->storageUrl);

		// copy already compressed file from other storage file
		if($this->isUploadFromOtherModel($path)) {

			// create relative webp path for storage
			$webp = Str::replace($this->getFileExtension($path), 'webp', $path);
			$webp = Str::replace($this->storageUrl, '', $webp);

			// check if webp file exists (upload already compressed)
			if($this->storage->exists($webp)) {
				// copy upload file
				$path = Str::replace($this->storageUrl, '', $path);
				$imgTarget = $this->target->storageFolder . $targetName . '.' . $this->getFileExtension($path);
				if($this->storage->exists($imgTarget)) { $this->storage->delete($imgTarget); }
				$this->storage->copy($path, $imgTarget);

				// copy webp file
				$webpTarget = $this->target->storageFolder . $targetName . '.webp';
				if($this->storage->exists($webpTarget)) { $this->storage->delete($webpTarget); }
				$this->storage->copy($webp, $webpTarget);

				// skip further compression in this method
				return $this->storage->url($imgTarget);
			}
		}

		// create image object from url
		$path = Str::before($path, '?');
		$path = str_starts_with($path,'http') ? file_get_contents($path) : $path;
		$image = Image::read($path);

		// if no height given, calculate correct aspect ratio
		if(empty($height)) { $height = intval( $width/$image->width() * $image->height() ); }

		// make webp file
		if($qualityWebp > 0) {
			$image = $image->cover(intval($width),$height)->toWebp($qualityWebp);
			$this->storage->put(Str::replace('.'.$imageType, '.webp', $imgName), $image);
		}

		// make image file
		$image = Image::read($path)->cover(intval($width),$height)->encodeByExtension($imageType, $qualityJpg);
		$this->storage->put($imgName, $image);

		// compress with tinypng
		if($imageType == 'png' && $this->getTinyPng()) {
			$imgTiny = Source::fromFile($this->storage->url($imgName));
			$this->storage->put($imgName, $imgTiny->result()->data());
		}

		$imgUrl = $this->storage->url($imgName);

		// add hash to image url
		if(!Str::contains($imgUrl,'?id=')) {
			$imgUrl .= '?id=' . Str::random(8);
		}

		return $imgUrl;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	UPLOAD HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function validateUpload($uploadFile, string $targetName): bool|string {

		if(empty($uploadFile)) { return false; }

		// is file in upload folder
		if(Str::containsAll($uploadFile, [$this->target->getTable(), '_upload/'])) {

			// check if upload already processed in preview mode
			$uploadFile = Str::replace($this->storageUrl, '', $uploadFile);
			$targetName = Str::replace($this->storageUrl, '', $targetName);

			// overwrite missing upload file with existing target file
			if(!$this->storage->exists($uploadFile) && $this->storage->exists($targetName)) {
				return $this->storage->url($targetName);
			}

			return true;
		}

		// is file from other model
		return $this->isUploadFromOtherModel($uploadFile);
	}


	public function isUploadFromOtherModel($uploadFile): bool {

		if(empty($uploadFile)) { return false; }

		// new upload if file from other model
		if(!Str::contains($uploadFile, $this->target->storageFolder)) { return true; }

		return false;
	}


	public function deleteUploadFile($property): void {

		if(Str::containsAll($property, [$this->target->getTable(), '_upload/'])) {

			// remove storage path from url
			$property = Str::replace($this->storage->url(''), '', $property);

			if($this->storage->exists($property)) { $this->storage->delete($property); }
		}
	}


	public function getFileExtension(string $filePath): ?string {

		if(empty($filePath)) { return null; }

		$filePath = explode('?',$filePath)[0];

		return strtolower( pathinfo($filePath)['extension'] );
	}


	/**
	 * get filename and extenstion without timestamp
	 */

	public function getFileName(string $filePath): ?string {

		if(empty($filePath)) { return null; }

		$basename = pathinfo($filePath)['basename'];
		return preg_replace('/\d{10,}-/', '', $basename);
	}


	public function getFileSize(string $filePath): ?float {

		if(empty($filePath)) { return null; }

		// remove storage url from file
		$relativeFile = Str::replace($this->storageUrl, '', $filePath);

		// is external file
		if(Str::startsWith($relativeFile,'http')) {

			$data = get_headers($filePath, true);
			$size = isset($data['Content-Length']) ? (int) $data['Content-Length'] : 0;

			// return size in kb
			return round($size/1000);
		}

		// get file size
		return round($this->storage->size($relativeFile)/1000);
	}


	public function createSlug(string $slugInput, string $default = ''): string {

		$slugify = new Slugify();

		// throw error if no input is given
		if(empty($slugInput) && empty($default)) {
			throw new \Exception('No slug input given');
		}

		return $slugify->slugify( empty($slugInput) ? $default : $slugInput );
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FILE PROCESSING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function moveFileFromUpload(string $property, string $targetName=null, bool $overwriteProp=true): ?string {

		$uploadFile = $this->target[$property];
		$uploadFileProps = pathinfo($uploadFile);

		// define new file path
		$fileName = $this->target->storageFolder . ($targetName ??  $uploadFileProps['basename']);

		$fileUrl = $this->moveFile($uploadFile, $fileName);

		if($fileUrl && $overwriteProp) {
			$this->deleteUploadFile($this->target[$property]);
			$this->target[$property] = $fileName;
		}

		return $fileUrl;
	}


	public function moveFile($uploadFile, string $targetName): ?string {

		// validate upload
		$validate = $this->validateUpload($uploadFile, $targetName);
		if($validate !== true) { return null; }

		// remove storage url from file
		$relativeUpload = Str::replace($this->storageUrl, '', $uploadFile);
		$relativeFile = Str::replace($this->storageUrl, '', $targetName);

		// skip on external file
		if(Str::startsWith($relativeUpload,'http')) { return $uploadFile; }

		// move file to model subfolder
		if($this->storage->exists($relativeFile)) { $this->storage->delete($relativeFile); }
		if($this->storage->exists($relativeUpload)) { $this->storage->move($relativeUpload, $relativeFile); }
		else {
			Log::critical("Job failed: " . get_class($this) . ". File not found on moveFile(). Model name: " . $this->target->name);
		}

		return $targetName;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VIDEO PROCESSING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function setVideoDuration(string $property, string $durationProp = null): ?string {

		$file = $this->target[$property] ?? null;
		if(empty($file)) { return null; }

		// duration already calculated?
		if(!empty($durationProp) && !empty($this->target[$durationProp])) { return null; }
		else if(Str::contains($this->target->storageFolder,$file)) { return null; }

		// add storage url to file if relative
		if(!Str::startsWith($file, 'http')) { $file = Str::start($file, $this->storageUrl); }

		// get duration
		$duration = shell_exec(config('app.ffmpeg').' -i "'.$file.'" 2>&1 | grep Duration | cut -d \' \' -f 4 | sed s/,//');
		$duration = explode('.', $duration)[0]; // remove frames from duration
		$duration = explode(':',$duration); // split into hours, minutes and seconds
		$duration = $duration[0] == '00' ? $duration[1].':'.$duration[2] : implode(':',$duration);

		// update property
		if(!empty($durationProp)) { $this->target[$durationProp] = $duration; }

		return $duration;
	}


	public function createVideoPreview(string $property): ?string {

		$file = $this->target[$property] ?? null;
		if(empty($file)) { return null; }

		// preview already calculated?
		if(Str::contains($this->target->storageFolder,$file)) { return null; }

		// add storage url to file if relative
		if(!Str::startsWith($file, 'http')) { $file = Str::start($file, $this->storageUrl); }

		// create previews folder if not exists
		$storageTemp = Storage::disk('temp');
		$previewsFolder = config('filesystems.disks.temp.root').'/previews/';
		if(!$storageTemp->exists('previews')) { $storageTemp->makeDirectory('previews'); }

		// create temporary file
		$tempId 	= $this->target->id;
		$tempFile 	= $previewsFolder . $tempId . '.png';
		$result 	= shell_exec(config('app.ffmpeg').' -ss 00:00:01.000 -i '.$file.' -vframes 1 '.$tempFile . ' 2>&1');

		// @codeCoverageIgnoreStart
		// check for successful video image
		if(!$storageTemp->exists('previews/'.$tempId.'.png')) {
			return Log::critical("Unable to create temp video file: for video " . $this->target[$property]);
		}
		// @codeCoverageIgnoreEnd

		// resize preview file
		$preview = Image::read($tempFile)->scale(160)->toJpeg(90);

		// save preview file
		$targetFile = $this->target->storageFolder . 'preview-'.$property.'.jpg';
		$this->storage->put($targetFile, $preview);

		// delete temp file
		$storageTemp->delete('previews/'.$tempId.'.png');

		return $this->storage->url($targetFile);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TINY PNG
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function getTinyPng(): ?Tinify {

		if(empty(config('app.tinypng'))) { return null; }

		if($this->tinyPng) { return $this->tinyPng; }

		$this->tinyPng = new Tinify();
		$this->tinyPng->setKey(config('app.tinypng'));

		return $this->tinyPng;
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
