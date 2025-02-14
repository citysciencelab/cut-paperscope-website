<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Security;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\Storage;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UnrestrictedFileUploadTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    XHR UPLOAD
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_successful_xhr() {

		if(!config('app.features.app_accounts')) {	return $this->assertTrue(true);	}

		// arrange
		$this->seedWithStorage();
		$user = $this->loginAsUser();
		$data = [
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'id' => $user->id,
			'file_type' => 'image',
			'file' => UploadedFile::fake()->image('image.jpg'),
		];

		// act
		$this->post('api/user/upload', $data);

		// assert: file exists
		$this->assertFileExists(storage_path('app/temp/userupload/'.$user->id.'/image.jpg'));
		Storage::disk('temp')->deleteDirectory('userupload/'.$user->id);
	}


	public function test_backend_successful_xhr() {

		// arrange: delete file first if existing
		Storage::disk('testing')->delete('unrestricted-uploads/image.jpg');

		// arrange
		$this->seedWithStorage();
		$this->loginAsEditor();
		$data = [
			'folder' => 'unrestricted-uploads',
			'storage' => 'testing',
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'file' => UploadedFile::fake()->image('image.jpg'),
		];

		// act
		$this->post('api/backend/file-upload', $data, $this->getBackendHeaders());

		// assert: file exists
		$this->assertFileExists(storage_path('app/public/testing/unrestricted-uploads/image.jpg'));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    XHR BAD EXTENSIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


/*
	public function test_ufu_xhr_extensions(string $filename) {

		if (!config('app.features.app_accounts')) {	return $this->assertTrue(true);	}

		// arrange
		$this->seedWithStorage();
		$user = $this->loginAsUser();
		$data = [
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'id' => $user->id,
			'file_type' => 'image',
			'file' => UploadedFile::fake()->createWithContent($filename,"<?php exit('failed'); ?>"),
		];

		// act
		$response = $this->post('api/user/upload', $data, ['Accept' => 'application/json']);

		// assert
		$response->assertStatus(str_ends_with($filename,'.jpg') || str_ends_with($filename,'.svg') ? 200 : 422);

		Storage::disk('temp')->deleteDirectory('userupload/'.$user->id);
	}
*/

/*
	public function test_backend_ufu_xhr_extensions(string $filename) {

		// arrange
		$this->seedWithStorage();
		$this->loginAsEditor();
		$data = [
			'folder' => 'unrestricted-uploads',
			'storage' => 'testing',
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'file' => UploadedFile::fake()->createWithContent($filename,"<?php exit('failed'); ?>"),
		];

		// act
		$response = $this->post('api/backend/file-upload', $data, $this->getBackendHeaders());

		// assert: response
		$response->assertStatus(422);

		// assert: file doesnt exists
		$this->assertFileDoesNotExist(storage_path('app/public/testing/unrestricted-uploads/'.$filename));
	}
*/

/*
	static public function provideExtensionData() {

		return [
			'php extension default' => ['image.php'],
			'php extension php5' => ['image.php5'],
			'hidden php extension' => ['file.p.phphp'],
			'php with slash' => ['image.php/'],
			'php double extension with jpg' => ['file.php.jpg'],
			'php double extension with svg' => ['file.php.svg'],
			'php converted on iis' => ['web”config'],
			'txt extension' => ['file.txt.jpg'],
			'bmp file' => ['file.bmp'],
			'htacess shortname' => ['HTACCE~1'],
			'trailing spaces' => ['file.asp … … . . .. ..'],
			'php multiple extensions' => ['file.txt.jpg.php'],
			'jpg multiple extensions' => ['file.txt.php.jpg'],
			'extension with special characters' => ['file.asax:.jpg'],
			'null control character' => ["file.txt\0.jpg"],
		];
	}
*/


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    XHR MIME TYPE FAKE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



public function test_ufu_xhr_mimetype() {

		if (!config('app.features.app_accounts')) {	return $this->assertTrue(true);	}

		// arrange
		$this->seedWithStorage();
		$user = $this->loginAsUser();
		$file = UploadedFile::fake()->create('test.phpbmp', 10, 'image/jpeg');
		$data = [
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'id' => $user->id,
			'file_type' => 'image',
			'file' => $file,
		];

		// act
		$response = $this->post('api/user/upload', $data, ['Accept' => 'application/json']);

		// assert
		$response->assertStatus(422);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    XHR BAD CONTENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// [TODO] Work in progress
	/** @dataProvider provideContentData */
	/*
	public function test_ufu_xhr_content(string $filename, $content) {

		// arrange
		$this->seedWithStorage();
		$this->loginAsEditor();
		$data = [
			'folder' => 'tests',
			'storage' => 'testing',
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'file' => UploadedFile::fake()->createWithContent($filename,$content),
		];

		// act
		$response = $this->post('api/backend/file-upload',$data, $this->getBackendHeaders());

		// assert
		$response->assertStatus(422);

		// assert: file doesnt exists
		$this->assertFileDoesNotExist(storage_path('app/public/tests/'.$filename));
	}


	static public function provideContentData() {

		return [
			'bad gif content' => ['image.gif', "GIF89a<?php exit('failed'); ?>"],
		];
	}
	*/



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class

