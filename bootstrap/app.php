<?php

// Laravel
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// App
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		health: '/status',
		channels: __DIR__.'/../routes/broadcast/channels.php',
		then: function() {

			// route validation rules
			Route::pattern('id', '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}');
			Route::pattern('parent', '[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89aAbB][a-f0-9]{3}-[a-f0-9]{12}');
			Route::pattern('vue_capture', '^(?!js|css|fonts|img|svg|video|storage)([\w\d\/\.-]*)$');
			Route::pattern('username', '^[a-z0-9]([._-](?![._-])|[a-z0-9]){1,29}[a-z0-9]$');

			// optional language prefix for web routes
			$langs = config('app.available_locales');
			$locale = array_search(request()->segment(1), $langs);
			$locale = $locale !== false ? $langs[$locale] : null;

			// web routes
			$webRoutes = Route::prefix($locale)->middleware('web');
			$webRoutes = $webRoutes->group(base_path('routes/web/web_app.php'));
			if(config('app.features.backend')) {
				$webRoutes->group(base_path('routes/web/web_backend.php'));
				$webRoutes->group(base_path('routes/web/web_auth.php'));
			}
			if(config('app.features.shop')) {
				$webRoutes->group(base_path('routes/web/web_shop.php'));
			}

			// api routes
			$apiRoutes = Route::prefix('api')->middleware('api')->group(base_path('routes/api/api_app.php'));
			if(config('app.features.backend')) {
				$apiRoutes->group(base_path('routes/api/api_backend.php'));
				$apiRoutes->group(base_path('routes/api/api_auth.php'));
			}
			if(config('app.features.shop')) {
				$apiRoutes->group(base_path('routes/api/api_shop.php'));
			}

			// api fallback (force json response)
			Route::prefix('api')->middleware('api')->get('/{api_capture?}',function() {
				return response()->json(['status' => 'error', 'message' => trans('api.not_found')], 404);
			});
		}
	)
	->withMiddleware(function (Middleware $middleware) {

		$middleware->use([
			\Illuminate\Http\Middleware\TrustHosts::class,
			\Illuminate\Http\Middleware\TrustProxies::class,
			\Illuminate\Http\Middleware\HandleCors::class,
			\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
			\Illuminate\Http\Middleware\ValidatePostSize::class,
			\Illuminate\Foundation\Http\Middleware\TrimStrings::class,
			\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
		]);

		$middleware->group('web', [
			\App\Http\Middleware\EncryptCookies::class,
			\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
			\Illuminate\Session\Middleware\StartSession::class,
			\App\Http\Middleware\AuthenticateSession::class,
			\App\Http\Middleware\SetUserLanguage::class,
			\Illuminate\View\Middleware\ShareErrorsFromSession::class,
			\App\Http\Middleware\VerifyCsrfToken::class,
			\Illuminate\Routing\Middleware\SubstituteBindings::class,
		]);

		$middleware->group('api', [
			\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
			\App\Http\Middleware\AuthenticateSession::class,
			\App\Http\Middleware\SetUserLanguage::class,
			'throttle:api',
			\Illuminate\Routing\Middleware\SubstituteBindings::class,
			\Illuminate\Routing\Middleware\SubstituteBindings::class,
		]);

		$middleware->alias([
			'auth' => \App\Http\Middleware\Authenticate::class,
			'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
			'signed' => \App\Http\Middleware\ValidateSignature::class,
			'backend.user' => \App\Http\Middleware\VerifyBackendUser::class,
		]);

		$middleware->redirectGuestsTo('/login');
	})
	->withBroadcasting(
		__DIR__.'/../routes/broadcast/channels.php',
		['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
	)
	->withExceptions(function (Exceptions $exceptions) {
		Integration::handles($exceptions);
	})->create();
