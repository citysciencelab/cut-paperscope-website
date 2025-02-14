<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\Backend\FileManager;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Http\UploadedFile;
	use Mockery;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class BackendFileUploadControllerTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FILE UPLOAD XHR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_backend_file_upload() {

		// arrange
		$this->seed();
		$this->loginAsEditor();
		$file = $this->createImageFile();

		// act
		$this->post('api/backend/file-upload', [
			'folder' => 'test-folder',
			'storage' => config('filesystems.default'),
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'file' => $file,
		]);

		$this->storage->assertExists('pages/_upload/test.jpg');
	}


	public function test_mailicious_file_upload() {

		// arrange
		$this->seed();
		$this->loginAsEditor();
		$file = UploadedFile::fake()->createWithContent('.env', 'APP_NAME=Laravel');

		// act
		$response = $this->post('api/backend/file-upload', [
			'folder' => '../../../',
			'storage' => config('filesystems.default'),
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'file' => $file,
		]);

		// assert: form validation error
		$response->assertStatus(302);
	}


	public function test_app_user_not_allowed_to_upload() {

		// arrange
		$this->seed();
		$this->loginAsUser();
		$file = UploadedFile::fake()->image('avatar.jpg');

		// act
		$response = $this->post('api/backend/file-upload', [
			'folder' => 'test-folder',
			'storage' => config('filesystems.default'),
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'file' => $file,
		]);

		// assert: form validation error
		$response->assertStatus(302);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FILE UPLOAD TUS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_file_upload_tus_post_request() {

		// arrange
		$this->seed();
		$this->mockTusServer();
		$this->loginAsEditor();
		$file = UploadedFile::fake()->image('avatar.jpg');

		// act
		$response = $this->post('api/backend/tus', [],[
			'Upload-Length' => $file->getSize(),
			'Content-Length' => 0,
			'X-Stream-Offset' => '78fb153f02e9d3a43d4e5a81273eb716=',
			'Upload-Metadata' => 'folder '.base64_decode('test-folder/').',filename '.base64_encode('avatar.jpg'),
		]);

		// assert
		$response->assertStatus(201);
		$response->assertHeader('Location', 'http://localhost:8000/api/backend/tus/test');
	}


	public function test_missing_stream_offset() {

		// arrange
		$this->seed();
		$this->mockTusServer();
		$this->loginAsEditor();
		$file = UploadedFile::fake()->image('avatar.jpg');

		// act
		$response = $this->post('api/backend/tus', [],[
			'Upload-Length' => $file->getSize(),
			'Content-Length' => 0,
			'Upload-Metadata' => 'folder '.base64_decode('test-folder/').',filename '.base64_encode('avatar.jpg'),
		]);

		// assert
		$response->assertStatus(403);
	}


	public function test_upload_hidden_file() {

		// arrange
		$this->seed();
		$this->mockTusServer('.htaccess');
		$this->loginAsEditor();

		// arrange: create .htaccess file
		$file = UploadedFile::fake()->createWithContent('.htaccess', 'APP_NAME=Laravel');

		// act
		$response = $this->post('api/backend/tus', [],[
			'Upload-Length' => $file->getSize(),
			'Content-Length' => 0,
			'X-Stream-Offset' => '78fb153f02e9d3a43d4e5a81273eb716=',
			'Upload-Metadata' => 'folder '.base64_decode('test-folder/').',filename '.base64_encode('.htaccess'),
		]);

		// assert
		$response->assertStatus(422);
	}


	public function test_wrong_folder_input() {

		// arrange
		$this->seed();
		$this->mockTusServer('avatar.jpg', '%alert()');
		$this->loginAsEditor();

		// arrange: create .htaccess file
		$file = UploadedFile::fake()->image('avatar.jpg');

		// act
		$response = $this->post('api/backend/tus', [],[
			'Upload-Length' => $file->getSize(),
			'Content-Length' => 0,
			'X-Stream-Offset' => '78fb153f02e9d3a43d4e5a81273eb716=',
			'Upload-Metadata' => 'folder '.base64_decode('%alert()').',filename '.base64_encode('.htaccess'),
		]);

		// assert
		$response->assertStatus(422);
	}


	public function test_file_upload_tus_patch_request() {

		// arrange
		$this->seed();
		$this->mockTusServer();
		$this->loginAsEditor();
		$file = UploadedFile::fake()->image('avatar.jpg');

		// assert: folder does not exist
		$this->storage->assertMissing('test-folder/');

		// act
		$this->patch('api/backend/tus', [],[
			'Upload-Length' => $file->getSize(),
			'Content-Length' => 0,
			'X-Stream-Offset' => '78fb153f02e9d3a43d4e5a81273eb716=',
			'Upload-Metadata' => 'folder '.base64_decode('test-folder/').',filename '.base64_encode('avatar.jpg'),
		]);

		// assert
		$this->storage->assertExists('test-folder/');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MOCK TUS SERVER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function mockTusServer(string $filename = 'avatar.jpg', string $folder='test-folder/') {

		// mock request
		/** @var \Mockery\LegacyMockInterface $request  */
		$request = Mockery::mock(\TusPhp\Request::class);
		$request->shouldReceive('extractMeta')->with('folder')->andReturn($folder);
		$request->shouldReceive('extractFilename')->andReturnUsing(function() use ($filename) {
			return $filename;
		});
		$request->shouldReceive('key')->andReturn('test-key');

		// mock cache
		/** @var \Mockery\LegacyMockInterface $cache  */
		$cache = Mockery::mock(\TusPhp\Cache\FileStore::class);
		$cache->shouldReceive('get')->with('test-key')->andReturn([
			'metadata' => [
				'folder' => $folder,
			],
		]);

		// mock server
		$server = Mockery::mock(\TusPhp\Tus\Server::class);
		$server->shouldReceive('getRequest')->andReturn($request);
		$server->shouldReceive('setUploadDir')->andReturn($server);
		$server->shouldReceive('getCache')->andReturn($cache);

		// create response
		$response = response('', 201, [
			'Location' => 'http://localhost:8000/api/backend/tus/test',
		]);

		$server->shouldReceive('serve')->andReturn($response);

		// apply tus server to app()
		$this->app->instance('tus-server', $server);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

