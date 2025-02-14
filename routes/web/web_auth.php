<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Laravel
	use Illuminate\Support\Facades\Route;
	use Illuminate\Support\Facades\Config;

	// App
	use App\Http\Controllers\App\AppController;
	use App\Http\Controllers\Auth\SsoController;
	use App\Http\Controllers\Auth\TokenController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	AUTH ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Single Sign On (without prefix)
	Route::get('/sso/google',[SsoController::class,'ssoGoogle']);
	Route::get('/sso/google/callback',[SsoController::class,'ssoGoogleCallback']);
	Route::get('/sso/facebook',[SsoController::class,'ssoFacebook']);
	Route::get('/sso/facebook/callback',[SsoController::class,'ssoFacebookCallback']);
	Route::get('/sso/apple',[SsoController::class,'ssoApple']);
	Route::post('/sso/apple/callback',[SsoController::class,'ssoAppleCallback']);

	// Token authentication for native mobile apps
	Route::post('/auth/token',[TokenController::class,'createToken'])->middleware('throttle:critical');
	Route::post('/auth/token/delete',[TokenController::class,'deleteToken'])->middleware('throttle:critical');

	// Default auth
	if(Config::get('app.features.app_accounts')) {

		Route::get("/login",[AppController::class,'showIndex'])->name('login');

		// Protected assets
		//Route::get('/asset/{type}/{value}', [AppController::class,'getPrivateAsset'])->name('asset.protected')->middleware(['auth','signed']);
	}
