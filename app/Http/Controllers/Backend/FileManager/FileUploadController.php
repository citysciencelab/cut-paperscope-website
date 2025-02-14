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
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Str;
	use Illuminate\Http\JsonResponse;

	// App
	use App\Traits\UploadValidationTrait;
	use App\Http\Requests\Backend\FileManager\FileUploadRequest;
	use App\Http\Requests\Backend\FileManager\FileUploadTusRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class FileUploadController extends BackendController {

	use UploadValidationTrait;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FILE UPLOAD XHR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function uploadFile(FileUploadRequest $request): JsonResponse {

		$validated = $request->validated();

		$me = Auth::user();

		// file input
		$folder = Str::lower( Str::finish($validated->folder ??  '/', '/') );
		$file = $request->file('file');
		$extension = '.' . $file->getClientOriginalExtension();

		// additional security check
		$filename = $file->getClientOriginalName();
		if(!$this->validateUpload($filename,$file)) { return $this->responseError(422); }

		// set correct storage for user
		$storage = $validated->storage ?? config('filesystems.default');
		if(!$me->isBackendUser()) { $storage = config('filesystems.default'); }	// force default storage for frontend users

		// save slugified file to storage
		$filename = str_replace($extension,'',$filename);
		$filename = $this->createSlug($filename) . Str::lower($extension);
		$storagePath = $folder . $filename;

		Storage::disk($storage)->put($storagePath, fopen($file, 'r+'));

		return $this->responseData($storagePath, 'data', ['message' => trans('api.file_upload')]);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FILE UPLOAD TUS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function uploadFileTus(FileUploadTusRequest $request): mixed {

		$me = Auth::user();

		// additional header validation
		$id = $request->header('X-Stream-Offset'); // hidden id for validation
		if($id!=='78fb153f02e9d3a43d4e5a81273eb716=') { return $this->responseError(403); }

		// set correct storage for user
		$storage = $request->storage ?? config('filesystems.default');
		if(!$me->isBackendUser()) { $storage = config('filesystems.default'); }	// force default storage for app users

		$server = app('tus-server');

		// inital upload request
		if($request->method() == 'POST') {

			// get params from request
			$filename = Str::lower( $server->getRequest()->extractFileName() );
			$folder = Str::lower( $server->getRequest()->extractMeta('folder') );

			// additional security check
			if(!$this->validateUpload($filename)) {	return $this->responseError(422); }
			if(!$this->validateFolder($folder)) { return $this->responseError(422); }

			// update upload folder based on request
			$server->setUploadDir(config('filesystems.disks.'.$storage.'.root').'/'.$folder);
		}

		// Create subfolder if not existing
		else if($request->method() == 'PATCH') {

			// get params from request
			$uploadKey = $server->getRequest()->key();
			$meta = $server->getCache()->get($uploadKey);
			$folder = Str::lower( $meta['metadata']['folder'] ?? '/' );

			// additional security check
			if(!$this->validateFolder($folder)) { return $this->responseError(422); }

			// create subfolder if not exists
			if(!Storage::disk($storage)->exists($folder)) { Storage::disk($storage)->makeDirectory($folder, 0775, true); }
		}

		return $server->serve();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
