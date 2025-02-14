<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Backend\FileManager;

	// Laravel
	use App\Http\Controllers\Backend\BackendController;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Support\Str;

	// App
	use App\Http\Requests\Backend\FileManager\FileManagerRequest;
	use App\Http\Requests\Backend\FileManager\FileDeleteRequest;
	use App\Http\Requests\Backend\FileManager\FolderCreateRequest;
	use App\Http\Requests\Backend\FileManager\FolderRenameRequest;
	use App\Http\Requests\Backend\FileManager\FolderDeleteRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class FileManagerController extends BackendController {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function getFolder(FileManagerRequest $request): JsonResponse {

		$validated = $request->validated();

		$folder = $validated->folder ?? '/';
		$files = [];

		// read folder content from filesystem
		$storage = $validated->storage ?? config('filesystems.default');
		foreach(Storage::disk($storage)->listContents($folder) as $f) {

			$item = [
				'path' => $f->path(),
				'basename' => basename($f->path()),
				'type' => $f->type(),
				'last_modified' => $f->lastModified(),
			];

			if($f->type() == 'file') {
				$item['size'] = $f->fileSize();
				$item['mimetype'] = $f->mimeType();
			}

			array_push($files, $item);
		}

		$files = $this->filterHidden($files);

		// sort by folder first
		$files = collect($files)->sortBy('path',SORT_NATURAL|SORT_FLAG_CASE)->sortBy('type')->values()->all();

		// [TESTING] paginator
		//$files = collect($files)->sortBy('basename',SORT_NATURAL|SORT_FLAG_CASE)->sortBy('type')->values();
		//return new LengthAwarePaginator($files->all(), $files->count(), 3);

		return $this->responseData($files);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FILE DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function deleteFile(FileDeleteRequest $request): JsonResponse {

		$validated = $request->validated();

		$path			= $validated->path;
		$storage 		= Storage::disk($validated->storage ?? config('filesystems.default'));
		$size 			= $storage->size($path);
		$last_modified 	= $storage->lastModified($path);

		// delete only if same size and last_modified in request
		if($validated->size != $size || $validated->last_modified != $last_modified) {
			return $this->responseError(401);
		}

		// delete file in storage
		if($storage->exists($path)) { $storage->delete($path); }

		return $this->responseData(trans('api.file_delete'), 'message');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FOLDER CREATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function createFolder(FolderCreateRequest $request): JsonResponse {

		$validated = $request->validated();

		$path 		= Str::lower( Str::finish($validated->path,'/') );
		$folder 	= $this->createSlug($validated->folder,'new-folder');
		$storage 	= Storage::disk($validated->storage ?? config('filesystems.default'));

		// create folder if path exists and folder not exists
		if(($path=='/' || $storage->exists($path)) && !$storage->exists($path.$folder)) {
			$storage->makeDirectory($path.$folder);
		}

		return $this->responseData(trans('api.folder_create'), 'message');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FOLDER RENAME
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function renameFolder(FolderRenameRequest $request): JsonResponse {

		$validated = $request->validated();

		$path 		= Str::finish($validated->path,'/');
		$folder 	= $validated->folder;
		$newFolder 	= $this->createSlug($validated->newFolder,'new-folder');
		$storage 	= Storage::disk($validated->storage ?? config('filesystems.default'));

		// prevent overwriting hidden files
		$hiddenFiles = $this->getHiddenFiles();
		if(in_array($folder,$hiddenFiles) || in_array($newFolder,$hiddenFiles)) {
			return $this->responseError(401);
		}

		// abort if new name already existing
		if($storage->exists($path.$newFolder)) { return $this->responseError(401, 'api.folder_already_exists'); }

		// abort if old folder doesnt exist
		if(!$storage->exists($path.$folder)) { return $this->responseError(); }

		// rename folder
		$storage->move($path.$folder, $path.$newFolder);

		return $this->responseData(trans('api.folder_rename'), 'message');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FOLDER DELETE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function deleteFolder(FolderDeleteRequest $request): JsonResponse {

		$validated	= $request->validated();
		$path 		= $validated->path;
		$storage 	= Storage::disk($validated->storage ?? config('filesystems.default'));

		if($storage->exists($path)) { $storage->deleteDirectory($path); }

		return $this->responseData(trans('api.folder_delete'), 'message');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	private function filterHidden(array $files): array {

		$hiddenFiles = $this->getHiddenFiles();

		$filtered = array_filter($files, fn($f) => !in_array($f['basename'],$hiddenFiles));

		return $filtered;
	}


	private function getHiddenFiles(): array {

		return ['.DS_Store', '.htaccess', '.htpasswd', '.gitignore', '.env', '.git'];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
