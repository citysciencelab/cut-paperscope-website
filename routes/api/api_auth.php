<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Laravel
	use Illuminate\Support\Facades\Route;

	// App
	use App\Http\Controllers\Auth\UploadController;
	use App\Http\Controllers\Auth\UserController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	$middleware = config('app.features.app_accounts') ? 'auth:sanctum' : ['auth:sanctum','backend.user'];

	Route::group(['middleware'=> $middleware],function(){

		// User
		Route::get('user', [UserController::class,'get'])->name('api.user');
		Route::post('user/save', [UserController::class,'save'])->name('api.user.save');
		Route::post('user/delete', [UserController::class,'delete'])->name('api.user.delete')->middleware('throttle:critical');
		Route::post('user/password', [UserController::class,'updatePassword'])->name('api.user.password')->middleware('throttle:critical');
		Route::post('user/image', [UserController::class,'updateImage'])->name('api.user.image')->middleware('throttle:critical');
		Route::post('user/image/delete', [UserController::class,'deleteImage'])->name('api.user.image.delete')->middleware('throttle:critical');

		// Upload
		Route::post('user/upload', [UploadController::class,'userUpload'])->name('api.user.upload')->middleware('throttle:critical');
	});


