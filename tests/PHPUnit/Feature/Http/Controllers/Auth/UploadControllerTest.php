<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\Auth;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Http\UploadedFile;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class UploadControllerTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoAppAccounts();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    USER UPLOAD XHR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

	/** @dataProvider provideUploadData */
	public function test_file_upload(string $fileType, string $fileExtension) {

		$this->seed();

		// arrange
		$user = $this->loginAsUser();

		// act
		$response = $this->post('api/user/upload', [
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'id' => $user->id,
			'file_type' => $fileType,
			'file' => UploadedFile::fake()->createWithContent('test.'.$fileExtension, 'content'),
		]);

		// assert: response
		$response->assertStatus(200);

		// assert: file exists
		$file = $response->json('file');
		$this->assertFileExists(storage_path('app/temp/'.$file));
	}


	static public function provideUploadData() {

		return [
			'image' => ['image', 'jpg'],
			'video' => ['video', 'mp4'],
			'audio' => ['audio', 'mp3'],
			'media' => ['media', 'gif'],
			'document' => ['doc', 'pdf'],
		];
	}


	public function test_not_allowed_to_upload_for_other_user() {

		$this->seed();

		// arrange
		$user = $this->loginAsUser();
		$otherUser = $this->createUser();

		// act
		$response = $this->post('api/user/upload', [
			'stream_offset' => '78fb153f02e9d3a43b4e5a81273ed716=',
			'id' => $otherUser->id,
			'file_type' => 'image',
			'file' => UploadedFile::fake()->image('avatar.jpg'),
		]);

		// assert
		$response->assertStatus(403);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

