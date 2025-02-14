<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Jobs\Base;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Config;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;
	use Mockery;

	// App
	use App\Jobs\Base\BaseJob;
	use App\Models\App\Base\Item;
	use App\Models\App\Base\Page;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BaseJobTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    IMAGE PROCESSING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_convert_jpeg() {

		// arrange
		$item = Item::factory()->createOne();
		$item->image = $this->createImageFile();

		// act
		$job = new BaseJob($item);
		$job->handle();
		$imgUrl = $job->convertJpeg('image',100,50,'image');
		$imgFile = $this->convertUrlToFilePath($item,$imgUrl);

		// assert
		$imgFile = Str::before($imgFile,'?');
		$this->assertFileExists(Str::replace('.jpg','.webp',$imgFile));
	}


	public function test_convert_png() {

		// arrange
		$item = Item::factory()->createOne();
		$item->image = $this->createImageFile('test.png', 'png');

		// act
		$job = new BaseJob($item);
		$job->handle();
		$imgUrl = $job->convertPng('image',100,50,'image');
		$imgFile = $this->convertUrlToFilePath($item,$imgUrl);

		// assert
		$imgFile = Str::before($imgFile,'?');
		$this->assertFileExists($imgFile);
		$this->assertFileExists(Str::replace('.png','.webp',$imgFile));
	}


	public function test_convert_image_without_webp() {

		// arrange
		$item = Item::factory()->createOne();
		$item->image = $this->createImageFile();

		// act
		$job = new BaseJob($item);
		$job->handle();
		$imgUrl = $job->convertJpeg('image',100,50,'image',true,50,0);
		$imgFile = $this->convertUrlToFilePath($item,$imgUrl);

		// assert
		$imgFile = Str::before($imgFile,'?');
		$this->assertFileExists($imgFile);
		$this->assertFileDoesNotExist(Str::replace('.jpg','.webp',$imgFile));
	}


	public function test_convert_jpg_from_other_model() {

		// arrange: first image
		$item = Item::factory()->createOne();
		$item->image = $this->createImageFile();

		// act: first image
		$job = new BaseJob($item);
		$job->handle();
		$imgUrl = $job->convertJpeg('image',100,50,'image');
		$imgFile = $this->convertUrlToFilePath($item,$imgUrl);

		// arrange: second image
		$item2 = Item::factory()->createOne();
		$item2->image = $imgUrl;

		// act: second image
		$job2 = new BaseJob($item2);
		$job2->handle();
		$imgUrl2 = $job2->convertJpeg('image',100,50,'image');
		$imgFile2 = $this->convertUrlToFilePath($item2,$imgUrl2);

		// assert: first image
		$imgFile = Str::before($imgFile,'?');
		$this->assertFileExists($imgFile);
		$this->assertFileExists(Str::replace('.png','.webp',$imgFile));

		// assert: second image
		$imgFile2 = Str::before($imgFile2,'?');
		$this->assertFileExists($imgFile2);
		$this->assertFileExists(Str::replace('.jpg','.webp',$imgFile2));
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_convert_png_with_tinypng() {

		// arrange
		$this->mockTinyPng();
		$item = Item::factory()->createOne();
		$item->image = $this->createImageFile();

		// act
		$job = new BaseJob($item);
		$job->handle();
		$imgUrl = $job->convertPng('image',100,50,'image');
		$imgFile = $this->convertUrlToFilePath($item,$imgUrl);

		// assert
		$imgFile = Str::before($imgFile,'?');
		$this->assertFileExists($imgFile);
		$this->assertFileExists(Str::replace('.jpg','.webp',$imgFile));
	}


	protected function convertUrlToFilePath($target, $fileUrl) {

		$storageDisk = Config::get('filesystems.disks.'.$target->storageDisk);
		$fileUrl = Str::remove($storageDisk['url'],$fileUrl);

		return Str::finish($storageDisk['root'],'/').$fileUrl;
	}


	protected function mockTinyPng() {

		config(['app.tinypng' => true]);

		// mock TinySource
		$mock = Mockery::mock('alias:\Tinify\Source');
		$mock->shouldReceive('fromFile')->andReturn($mock);
		$data = $mock->shouldReceive('data')->andReturn('test.jpg');
		$mock->shouldReceive('result')->andReturn($mock);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    UPLOAD HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_upload_validation() {

		// arrange
		$this->seed();
		$item = Item::factory()->createOne();
		$sourceImage = $this->createImageFile();
		$targetImage = $item->storageFolder.'test-target.jpg';

		// act
		$job = new BaseJob($item);
		$job->handle();

		// assert: image is new upload
		$result = $job->validateUpload($sourceImage, $targetImage);
		$this->assertTrue($result);

		// act: new upload not available, but target file exists
		$this->storage->move('items/_upload/test.jpg', $targetImage);
		$result = $job->validateUpload($sourceImage, $targetImage);

		// assert
		$this->assertEquals($result, $this->storage->url($targetImage));

		// act: upload from other item
		$this->storage->move($targetImage, 'items/999/test.jpg');
		$result = $job->validateUpload('items/999/test.jpg', $targetImage);

		// assert
		$this->assertTrue($result);

		// act: external upload
		$result = $job->validateUpload('https://www.hello-nasty.com/test.png', $targetImage);

		// assert
		$this->assertTrue($result);

		// assert: upload already processed by job
		$result = $job->validateUpload($item->storageFolder.'test.jpg', $targetImage);
		$this->assertFalse($result);
	}


	public function test_upload_validation_upload_not_exists_but_target() {

		// arrange
		$item = Page::factory()->createOne();
		$sourceImage = $this->createImageFile();
		$targetImage = $item->storageFolder.'test-target.jpg';

		// act
		$job = new BaseJob($item);
		$job->handle();

		// act: move upload to target
		$storageUrl = Storage::disk($item->storageDisk)->url('/');
		$sourceImage = Str::replace($storageUrl, '', $sourceImage);
		$targetImage = Str::replace($storageUrl, '', $targetImage);
		$this->storage->move($sourceImage,$targetImage);
		$result = $job->validateUpload($sourceImage, $targetImage);

		// assert
		$this->assertEquals($result, $this->storage->url($targetImage));
	}


	public function test_delete_upload_file() {

		// arrange
		$item = Item::factory()->createOne();
		$image = $this->createImageFile();

		// act
		$job = new BaseJob($item);
		$job->handle();
		$job->deleteUploadFile($image);

		// assert
		$this->assertFileDoesNotExist($this->storage->url($image));
	}


	/** @dataProvider provideFileExtensions */
	public function test_file_extension(string $imageUrl, mixed $extension) {

		// arrange
		$item = Item::factory()->createOne();

		// act
		$job = new BaseJob($item);
		$fileExtension = $job->getFileExtension($imageUrl);

		// assert
		$this->assertEquals($fileExtension, $extension);
	}


	static public function provideFileExtensions() {

		return [
			"expected input" => ['https://wwww.hello-nasty.com/image.jpg', 'jpg'],
			"relative input" => ['sub/folder/image.gif', 'gif'],
			"img url with query string" => ['image.png?query=string', 'png'],
			"dot in filename" => ['image.with.dot.jpg', 'jpg'],
			"empty file" => ['', null],
		];
	}


	/** @dataProvider provideFileNames */
	public function test_file_name($inputFile, $outputFile) {

		// assert
		$item = Item::factory()->createOne();

		// act
		$job = new BaseJob($item);
		$fileName = $job->getFileName($inputFile);

		// assert
		$this->assertEquals($fileName, $outputFile);
	}


	static public function provideFileNames() {

		return [
			'excepted input' => ['12349223422-test.jpg', 'test.jpg'],
			'complex filename' => ['12349223422-test_1205223423 file.jpg', 'test_1205223423 file.jpg'],
			'name without timestamp' => ['test.jpg', 'test.jpg'],
		];
	}


	public function test_file_size() {

		// arrange
		$item = Item::factory()->createOne();
		$file = $this->createImageFile();
		$fileSize = 1.0;

		// act
		$job = new BaseJob($item);
		$job->handle();
		$size = $job->getFileSize($file);

		// assert
		$this->assertEquals($size, $fileSize);
	}


	public function test_file_size_external() {

		// arrange
		$item = Item::factory()->createOne();
		$externalFile = 'https://www.hello-nasty.com/favicon.ico';

		// act
		$job = new BaseJob($item);
		$job->handle();
		$size = $job->getFileSize($externalFile);

		// assert
		$this->assertIsFloat($size);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FILE PROCESSING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_move_file_from_upload() {

		// arrange
		$item = Item::factory()->createOne();
		$item->file = $this->createFile("test.txt", "test content");

		// act
		$job = new BaseJob($item);
		$job->handle();
		$file = $job->moveFileFromUpload('file', 'new-file.txt');
		$file = $this->convertUrlToFilePath($item,$file);

		// assert
		$this->assertFileExists($file);
		$this->assertEquals(file_get_contents($file), 'test content');
	}


	public function test_move_null_file_from_upload() {

		// arrange
		$item = Item::factory()->createOne();
		$item->file = null;

		// act
		$job = new BaseJob($item);
		$job->handle();
		$file = $job->moveFileFromUpload('file', 'new-file.txt');

		// assert
		$this->assertNull($file);
	}


	public function test_move_file_not_found() {

		// arrange
		$item = Item::factory()->createOne();
		$item->file = 'test.txt';

		// assert
		Log::shouldReceive('critical')->once();

		// act
		$job = new BaseJob($item);
		$job->handle();
		$job->moveFile('test.txt', 'new-file.txt');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VIDEO PROCESSING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_set_video_duration() {

		// check if ffmpeg executable exists
		if(!file_exists(Config::get('app.ffmpeg'))) {
			$this->markTestSkipped('ffmpeg binary not found');
		}

		// arrange
		$videoFile = "https://www.w3schools.com/html/mov_bbb.mp4";
		$item = Item::factory()->createOne();
		$item->file = $videoFile;

		// act
		$job = new BaseJob($item);
		$job->handle();
		$duration = $job->setVideoDuration('file');

		// assert
		$this->assertEquals($duration, "00:10");
	}


	public function test_create_video_preview() {

		// check if ffmpeg executable exists
		if(!file_exists(Config::get('app.ffmpeg'))) {
			$this->markTestSkipped('ffmpeg binary not found');
		}

		// arrange
		$videoFile = "https://www.w3schools.com/html/mov_bbb.mp4";
		$item = Item::factory()->createOne();
		$item->file = $videoFile;

		// act
		$job = new BaseJob($item);
		$job->handle();
		$file = $job->createVideoPreview('file');
		$file = $this->convertUrlToFilePath($item,$file);

		// assert
		$this->assertFileExists($file);
		$this->assertStringContainsString('preview-file.jpg', $file);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    EXCEPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_exception() {

		// arrange
		$this->seed();
		$item = Item::factory()->createOne();

		// act
		$job = new BaseJob($item);

		// assert: error message in log file
		Log::shouldReceive('critical')->with('Job failed: App\Jobs\Base\BaseJob. Model name: '.$item->name.'. exception: test exception')->once();
		$job->failed(new \Exception('test exception'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

