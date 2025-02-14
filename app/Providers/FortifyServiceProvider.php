<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Providers;

	// Laravel
	use App\Actions\Fortify\RegisterNewUser;
	use App\Actions\Fortify\ResetUserPassword;
	use Illuminate\Cache\RateLimiting\Limit;
	use Illuminate\Http\Request;
	use Illuminate\Support\Str;
	use Illuminate\Support\ServiceProvider;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\RateLimiter;
	use Illuminate\Support\Facades\Hash;
	use Laravel\Fortify\Fortify;
	use Laravel\Fortify\Contracts\LoginResponse;
	use Laravel\Fortify\Contracts\RegisterResponse;
	use Laravel\Fortify\Contracts\VerifyEmailResponse;

	// App
	use App\Http\Controllers\Auth\UserController;
	use App\Http\Resources\Auth\UserResource;
	use App\Models\Auth\User;
	use App\Http\Requests\Auth\LoginRequest;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class FortifyServiceProvider extends ServiceProvider
{

	public function register() {

		// custom login response
		$this->app->instance(LoginResponse::class, new class implements LoginResponse { public function toResponse($request) {

			// check for backend login
			$isBackendRequest = false;
			if($request->hasHeader('X-Context') && $request->header('X-Context') === 'backend') {
				$isBackendRequest = true;
			}
			// referrer contains "backend"
			if($request->hasHeader('referer') && Str::contains($request->header('referer'),'backend')) {
				$isBackendRequest = true;
			}

			// check for correct roles on backend login
			if($isBackendRequest && !Auth::user()->isBackendUser()) {
				Auth::logout();
				return response()->json(['status' => 'error', 'message' => trans('api.unauthorized') ], 403);
			}

			// get full user model
			$user = (new UserController())->getMe();

			// prevent blocked user
			if($user->blocked) {
				return response()->json(['status' => 'error', 'message' => trans('api.user_blocked') ], 403);
			}

			// reqular app login
			return response()->json(['status' => 'success', 'data' => new UserResource($user) ], 200);
		}});


		// custom register response
		$this->app->instance(RegisterResponse::class, new class implements RegisterResponse { public function toResponse($request) {

			// get full user model
			$user = (new UserController())->getMe();

			return response()->json(['status' => 'success', 'message' => '', 'data' => new UserResource($user) ], 200);
		}});


		// custom response after verified email
		$this->app->instance(VerifyEmailResponse::class, new class implements VerifyEmailResponse { public function toResponse($request) {

			// get language from query string
			$fallbackLocale = config('app.fallback_locale');
			$locale = $request->query('lang', $fallbackLocale);
			app()->setLocale($locale);

			// get url from route
			$route = Str::after(Fortify::redirects('email-verification'), config('app.url'));
			$url = ($locale != $fallbackLocale ? $locale.'/' : '') . $route . '?verified=1';

			return redirect($url);
		}});
	}


	public function boot() {

		// Fortify classes
		Fortify::createUsersUsing(RegisterNewUser::class);
		Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

		// Custom Login with username or email
		Fortify::authenticateUsing(function (LoginRequest $request) {

			$validated = $request->validated();

			// find by mail or username
			$user = User::where('email', $validated['email'])->first();
			if(!$user) { $user = User::where('username', $validated['email'])->first(); }

			if ($user && Hash::check($validated['password'], $user->password)) { return $user; }
		});

		// Throttling
		//@codeCoverageIgnoreStart
		RateLimiter::for('login', function (Request $request) {
			return Limit::perMinute(5)->by($request->email.$request->ip());
		});
		RateLimiter::for('two-factor', function (Request $request) {
			return Limit::perMinute(5)->by($request->session()->get('login.id'));
		});
		// @codeCoverageIgnoreEnd

		// Auth routes
		Fortify::loginView(function() { return redirect( config('fortify.login') ); });
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
