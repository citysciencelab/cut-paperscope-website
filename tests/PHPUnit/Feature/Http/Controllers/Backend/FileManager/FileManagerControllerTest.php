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
	use Illuminate\Support\Arr;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class FileManagerControllerTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_get_folder() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();
		$folderData = [
			'folder' => '/',
			'storage' => config('filesystems.default'),
		];

		// act
		$data = $this->postData('api/backend/file-manager',$folderData, $this->getBackendHeaders());
		$folderUsers = Arr::first($data, fn($i) => $i['basename']=='users');

		// assert: "users" folder
		$this->assertEquals('users',$folderUsers['path']);
		$this->assertEquals('users',$folderUsers['basename']);
		$this->assertEquals('dir',$folderUsers['type']);
	}


	public function test_get_file_in_subfolder() {

		// arrange
		$this->seed();
		$admin = $this->loginAsAdmin();
		$folderData = [
			'folder' => '/'.$admin->storageFolder,
			'storage' => config('filesystems.default'),
		];

		// act
		$data = $this->postData('api/backend/file-manager',$folderData, $this->getBackendHeaders());
		$file = Arr::first($data, fn($i) => $i['type']=='file');

		// assert: file attributes
		$this->assertEquals($admin->storageFolder.$file['basename'],$file['path']);
		$this->assertEquals('file',$file['type']);
		$this->assertIsInt($file['size']);
	}


	public function test_user_not_allowed_to_get_folder() {

		// arrange
		$this->seed();
		$this->loginAsUser();
		$folderData = [
			'folder' => '/',
			'storage' => config('filesystems.default'),
		];

		// act and assert
		$this->postError('api/backend/file-manager',$folderData,$this->getBackendHeaders(), 'api.unauthorized');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FILE DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete_file() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// arrange: create file
		$file = $this->createImageFile();
		$file = $this->storageUrlToPath($file);

		// act
		$response = $this->post('api/backend/file-manager/delete', [
			'path' => $file,
			'storage' => config('filesystems.default'),
			'size' => $this->storage->size($file),
			'last_modified' => $this->storage->lastModified($file),
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
		$this->storage->assertMissing($file);
	}


	public function test_user_not_allowed_to_delete() {

		// arrange
		$this->seed();
		$this->loginAsUser();

		// arrange: create file
		$file = $this->createImageFile();
		$file = $this->storageUrlToPath($file);

		// act
		$response = $this->post('api/backend/file-manager/delete', [
			'path' => $file,
			'storage' => config('filesystems.default'),
			'size' => $this->storage->size($file),
			'last_modified' => $this->storage->lastModified($file),
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
		$this->storage->assertExists($file);
	}


	public function test_delete_file_with_wrong_size() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// arrange: create file
		$file = $this->createImageFile();
		$file = $this->storageUrlToPath($file);

		// act
		$response = $this->post('api/backend/file-manager/delete', [
			'path' => $file,
			'storage' => config('filesystems.default'),
			'size' => 123,
			'last_modified' => $this->storage->lastModified($file),
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(401);
		$this->storage->assertExists($file);
	}


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FOLDER CREATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_create_folder() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// assert: folder doesn't exist
		$this->storage->assertMissing('test-folder/');

		// act
		$response = $this->post('api/backend/file-manager/folder/create', [
			'folder' => 'test-folder',
			'storage' => config('filesystems.default'),
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
		$this->storage->assertExists('test-folder/');
	}


	public function test_user_not_allowed_to_create_folder() {

		// arrange
		$this->seed();
		$this->loginAsUser();

		// assert: folder doesn't exist
		$this->storage->assertMissing('test-folder/');

		// act
		$response = $this->post('api/backend/file-manager/folder/create', [
			'folder' => 'test-folder',
			'storage' => config('filesystems.default'),
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
		$this->storage->assertMissing('test-folder/');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FOLDER RENAME
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_rename_folder() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();
		$this->storage->makeDirectory('test-folder-old/');

		// act
		$response = $this->post('api/backend/file-manager/folder/rename', [
			'path' => '/',
			'folder' => 'test-folder-old',
			'newFolder' => 'test-folder-renamed',
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
		$this->storage->assertExists('test-folder-renamed/');
		$this->storage->assertMissing('test-folder-older/');
	}


	public function test_user_not_allowed_to_rename_folder() {

		// arrange
		$this->seed();
		$this->loginAsUser();
		$this->storage->makeDirectory('test-folder-old/');

		// act
		$response = $this->post('api/backend/file-manager/folder/rename', [
			'path' => '/',
			'folder' => 'test-folder-old',
			'newFolder' => 'test-folder-renamed',
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(403);
		$this->storage->assertExists('test-folder-old/');
		$this->storage->assertMissing('test-folder-renamed/');
	}


	public function test_rename_folder_doesnt_exist() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// act
		$response = $this->post('api/backend/file-manager/folder/rename', [
			'path' => '/',
			'folder' => 'test-folder-old',
			'newFolder' => 'test-folder-renamed',
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(404);
		$this->storage->assertMissing('test-folder-renamed/');
	}

	public function test_not_allowed_to_rename_hidden_folder() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();
		$this->storage->makeDirectory('.git');

		// act
		$response = $this->post('api/backend/file-manager/folder/rename', [
			'path' => '/',
			'folder' => '.git',
			'newFolder' => 'public',
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(401);
		$this->storage->assertMissing('public/');
	}


	public function test_not_allowed_to_rename_existing_folder() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// arrange: create folder
		$this->storage->makeDirectory('test-folder-old/');
		$this->storage->makeDirectory('test-folder-renamed/');

		// act
		$response = $this->post('api/backend/file-manager/folder/rename', [
			'path' => '/',
			'folder' => 'test-folder-old',
			'newFolder' => 'test-folder-renamed',
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(401);
		$this->storage->assertExists('test-folder-old/');
		$this->storage->assertExists('test-folder-renamed/');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FOLDER DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_delete_folder() {

		// arrange
		$this->seed();
		$this->loginAsAdmin();

		// arrange: create folder
		$this->storage->makeDirectory('test-folder/');
		$this->storage->assertExists('test-folder/');

		// act
		$response = $this->post('api/backend/file-manager/folder/delete', [
			'path' => '/test-folder',
		], $this->getBackendHeaders());

		// assert
		$response->assertStatus(200);
		$this->storage->assertMissing('test-folder/');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

