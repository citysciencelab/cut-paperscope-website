<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Middleware;

	// Laravel
	use Closure;
	use Illuminate\Auth\Middleware\Authenticate as Middleware;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Facades\Auth;
	use Symfony\Component\HttpFoundation\Response;

	// App
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class Authenticate extends Middleware {


 	public function handle($request, Closure $next, ...$guards): Response {

		$routeName = $request->route()->getName();

		// authenticate user on verification route
		if($routeName=='verification.verify' && !Auth::user()) {

			// Validate existing user
			$validator = Validator::make($request->route()->parameters, [
				'id' => 'required|string|uuid|exists:users,id'
			]);

			// login user only if not verified
			if($validator->passes()) {
				$user = User::find($request->route()->id);
				if($user && !$user->hasVerifiedEmail()) { Auth::login($user); }
			}
		}
		// authenticate native app user on resend verification mail
		if($routeName=='verification.send' && $request->hasHeader('X-Native-App')) {
			$user = auth('sanctum')->user();
			if($user) { Auth::login($user); }
		}

		// check if user is logged in
		$this->authenticate($request, $guards);

        return $next($request);
    }


	// Attribute overwrite: Get the path the user should be redirected to when they are not authenticated.
	protected function redirectTo(Request $request): ?string {

		return $request->expectsJson ? null : url( config('fortify.login') );
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
