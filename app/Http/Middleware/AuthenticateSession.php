<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Middleware;

	// Laravel
	use Closure;
	use Illuminate\Auth\AuthenticationException;
	use Illuminate\Contracts\Auth\Factory as AuthFactory;
	use Symfony\Component\HttpFoundation\Response;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AuthenticateSession {


	/**
	 *
	 * The authentication factory implementation.
	 *
	 * @var \Illuminate\Contracts\Auth\Factory
	 */
	protected $auth;

	/**
	 * Use sanctum instead of default driver "web"
	 */
	private $authDriver = 'sanctum';

	/**
	 * Create a new middleware instance.
	 *
	 * @param  \Illuminate\Contracts\Auth\Factory  $auth
	 * @return void
	 */

	public function __construct(AuthFactory $auth) {

		$this->auth = $auth;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HANDLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */

	public function handle($request, Closure $next): Response {

		// skip if no session data available
		if(!$request->hasSession() || !$request->user()) { return $next($request); }

		// validate remember cookie for session
		if ($this->guard()->viaRemember()) {
			$passwordHash = explode('|', $request->cookies->get($this->auth->getRecallerName()))[2] ?? null;
			if (!$passwordHash || $passwordHash != $request->user()->getAuthPassword()) {
				$this->logout($request);
			}
		}

		// add password hash to session
		if (!$request->session()->has('password_hash_'.$this->authDriver)) {
			$this->storePasswordHashInSession($request);
		}

		// validate password hash in session
		if ($request->session()->get('password_hash_'.$this->authDriver) !== $request->user()->getAuthPassword()) {
			$this->logout($request);
		}

		return tap($next($request), function () use ($request) {

			if (!is_null($this->guard()->user())) {
				$this->storePasswordHashInSession($request);
			}
		});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SESSION HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Store the user's current password hash in the session.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	protected function storePasswordHashInSession($request): void  {

		if (!$request->user() || !$request->session())  { return; }

		$request->session()->put([
			'password_hash_'.$this->authDriver => $request->user()->getAuthPassword(),
		]);
	}


	/**
	 * Log the user out of the application.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 *
	 * @throws \Illuminate\Auth\AuthenticationException
	 */
	protected function logout($request): void {

		$this->guard()->logoutCurrentDevice();

		$request->session()->flush();

		throw new AuthenticationException('Unauthenticated.', [$this->authDriver] );
	}


	/**
	 * Get the guard instance that should be used by the middleware.
	 *
	 * @return \Illuminate\Contracts\Auth\Factory|\Illuminate\Contracts\Auth\Guard
	 */
	protected function guard() {

		return $this->auth;
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}
