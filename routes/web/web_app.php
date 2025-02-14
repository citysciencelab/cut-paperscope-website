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
	use App\Http\Controllers\App\ProjectController;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	APP PUBLIC
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Index
	Route::get("",[AppController::class,'showIndex'])->name('index');

	// Mail Templates
	if(Config::get('app.env')=='local') {
		Route::get('preview/mail/contact', function() { return (new App\Mail\ContactMail(['message'=>'message text', 'name'=>'sender name', 'email'=>'sender@test.com'])); });
		Route::get('preview/notification/verify-notification', function() { $u = App\Models\Auth\User::whereEmail(env('ROOT_EMAIL'))->first(); return (new App\Notifications\VerifyRegisterNotification('token'))->toMail($u);});
		Route::get('preview/notification/reset-notification', function() { $u = App\Models\Auth\User::whereEmail(env('ROOT_EMAIL'))->first(); return (new App\Notifications\PasswordResetNotification('token','de'))->toMail($u);});
	}

	// Health
	Route::get('/debug-sentry', function () { throw new Exception('My first Sentry error!'); });
	Route::get('/debug-slack', function () { \Illuminate\Support\Facades\Log::critical("Slack error from Laravel"); return "OK"; });

	// project
	Route::get('project/geojson/{slug}', [ProjectController::class,'downloadGeoJson'])->name('api.project.geojson');
	Route::get('project/pdf', [ProjectController::class,'getPdf'])->name('project.pdf');
	Route::get('project/map', [ProjectController::class,'getMap'])->name('project.map');

	// Force all routes to Vue
	Route::fallback([AppController::class,'showIndex'])->name('index.vue');
