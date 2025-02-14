<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Laravel
	use Illuminate\Support\Facades\Route;

	// App
	use App\Helper\ApiRoutes;
	use App\Http\Controllers\Backend\Base\DashboardController;
	use App\Http\Controllers\Backend\FileManager\FileManagerController;
	use App\Http\Controllers\Backend\FileManager\FileUploadController;
	use App\Http\Controllers\Backend\FileManager\AwsS3Controller;
	use App\Http\Controllers\Auth\UserController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	BACKEND
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// protected backend routes
	Route::group(['middleware'=>['auth:sanctum','backend.user']],function(){

		// Models
		ApiRoutes::setModelBackendRoutes('item', false, 'Base\\Item');
		ApiRoutes::setModelBackendRoutes('page', false, 'Base\\Page');
		ApiRoutes::setModelBackendRoutes('fragment',true, 'Base\\Fragment');
		ApiRoutes::setModelBackendRoutes('setting', false, 'Base\\Setting');
		ApiRoutes::setModelBackendRoutes('product', false, 'Shop\\Product');
		ApiRoutes::setModelBackendRoutes('project');
		// [add model app routes]

		// User
		Route::post('backend/user', [UserController::class,'get'])->name('api.backend.user');
		Route::post('backend/user/list', [UserController::class,'getList'])->name('api.backend.user.list');
		Route::get('backend/user/roles', [UserController::class,'getRolesList'])->name('api.backend.user.roles');
		Route::post('backend/user/search', [UserController::class,'search'])->name('api.backend.user.search');

		// File Manager
		Route::post('backend/file-manager', [FileManagerController::class,'getFolder'])->name('api.backend.file-manager.list');
		Route::post('backend/file-manager/delete', [FileManagerController::class,'deleteFile'])->name('api.backend.file-manager.delete');
		Route::post('backend/file-manager/folder/create', [FileManagerController::class,'createFolder'])->name('api.backend.file-manager.folder.create');
		Route::post('backend/file-manager/folder/rename', [FileManagerController::class,'renameFolder'])->name('api.backend.file-manager.folder.rename');
		Route::post('backend/file-manager/folder/delete', [FileManagerController::class,'deleteFolder'])->name('api.backend.file-manager.folder.delete');

		// Dashboard
		Route::get('backend/dashboard', [DashboardController::class,'get'])->name('api.backend.dashboard');
		Route::get('backend/dashboard/analytics/{range}', [DashboardController::class,'getAnalytics'])->name('api.backend.dashboard.analytics')->whereIn('range',['week','month','year']);

		// Upload
		Route::post('backend/file-upload', [FileUploadController::class,'uploadFile'])->name('api.backend.file-upload');

		// AWS S3 Multipart upload
		Route::prefix('backend/s3/multipart')->group(function () {

			Route::options('/', [AwsS3Controller::class,'createPreflightHeader']);
			Route::post('/', [AwsS3Controller::class,'createMultipartUpload']);
			Route::get('/{uploadId}', [AwsS3Controller::class,'getUploadedParts']);
			Route::post('/{uploadId}/complete', [AwsS3Controller::class,'completeMultipartUpload']);
			Route::delete('/{uploadId}', [AwsS3Controller::class,'abortMultipartUpload']);
			Route::get('/{uploadId}/{partNumber}', [AwsS3Controller::class,'signPartUpload']);
		});
	});


	// TUS file uploads
	Route::any('backend/tus/{any?}', [FileUploadController::class,'uploadFileTus'])->where('any', '.*')->name('api.backend.tus');


