<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace App\Http\Controllers\Auth;

	// Laravel
	use Illuminate\Http\Request;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;
	use Laravel\Socialite\Facades\Socialite;
	use Laravel\Socialite\Two\User as SsoUser;

	// App
	use App\Http\Resources\Auth\UserResource;
	use App\Actions\Fortify\RegisterNewUser;
	use App\Http\Controllers\Backend\BackendController;

	// App Models
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS CONSTRUCT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SsoController extends BackendController {


	// model classes
	protected $modelClass = User::class;
	protected $modelResourceClass = UserResource::class;
	protected $modelListResourceClass = UserResource::class;

	// model relations
	protected $modelRelations = ['products'];
	protected $modelListRelations = [];

	// return model list with pagination
	protected $paginator = true;

	// properties
	protected bool $nativeApp = false;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GOOGLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function ssoGoogle() { return Socialite::driver('google')->stateless()->redirect(); }
	public function ssoGoogleCallback(Request $request) { return $this->ssoCallback($request,'google'); }


	protected function setUserFromGoogle(SsoUser &$ssoUser, array &$userProps): void {

		$userProps['email'] 	= $ssoUser->getEmail();
		$userProps['name'] 		= !empty($ssoUser->user['given_name']) ? $ssoUser->user['given_name'] : 'Gast';
		$userProps['surname'] 	= !empty($ssoUser->user['family_name']) ? $ssoUser->user['family_name'] : '---';
		$userProps['image'] 	= $ssoUser->user['picture'];

		// username
		$userProps['username'] = $userProps['nickname'] ?? explode('@', $userProps['email'])[0];
		$userProps['username'] = $this->ensureUniqueUsername($userProps['username']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FACEBOOK
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function ssoFacebook() { return Socialite::driver('facebook')->fields(['name','first_name','last_name'])->scopes(['email,public_profile'])->stateless()->redirect(); }
	public function ssoFacebookCallback(Request $request) { return $this->ssoCallback($request,'facebook'); }


	protected function setUserFromFacebook(SsoUser &$ssoUser, array &$userProps): void {

		$parts = explode(' ', $ssoUser->user['name']);

		$userProps['email'] 	= $ssoUser->getEmail();
		$userProps['name'] 		= $parts[0];
		$userProps['surname'] 	= count($parts)>1 ? $parts[1] : '';
		$userProps['image'] 	= $ssoUser->avatar;

		// username
		$userProps['username'] = $userProps['nickname'] ?? explode('@', $userProps['email'])[0];
		$userProps['username'] = $this->ensureUniqueUsername($userProps['username']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	APPLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function ssoApple() { return Socialite::driver('apple')->redirect(); }
	public function ssoAppleCallback(Request $request) { return $this->ssoCallback($request,'apple'); }


	protected function setUserFromApple(SsoUser &$ssoUser, array &$userProps): void {

		$userProps['email'] 	= $ssoUser->getEmail();
		$userProps['name'] 		= $ssoUser->user['name']['firstName'] ?? explode('@', $userProps['email'])[0];
		$userProps['surname'] 	= $ssoUser->user['name']['lastName'] ?? 'apple';
		$userProps['image'] 	= null;

		// username
		$userProps['username'] = $userProps['username'] ?? explode('@', $userProps['email'])[0];
		$userProps['username'] = $this->ensureUniqueUsername($userProps['username']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SSO CALLBACK
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function ssoCallback(Request $request, string $driver): RedirectResponse|JsonResponse {

		$this->nativeApp = $this->isNativeApp($request);

		// get code from request
		$code = $request->input('code') ?? $request->header('code');
		if(!$code) { return $this->responseSsoError('code_missing'); }

		// get user models from provider and database
		try {

			$ssoUser = $this->nativeApp ?
				Socialite::driver($driver)->stateless()->userFromToken($code)
			: 	Socialite::driver($driver)->stateless()->user();

			if(!$ssoUser) { return $this->responseSsoError('driver_user_not_found'); }

			$user = User::whereEmail($ssoUser->getEmail())->first();
		}
		catch(\Exception $e) {
			Log::error($e);
			return $this->responseSsoError('unknown_driver_error');
		}

		// existing user but not via this sso driver
		if($user && $user->sso_driver != $driver) {

			// abort if already verified
			if($user->hasVerifiedEmail()) {
				return $this->nativeApp ?
					$this->responseError(403, 'sso_mismatch')
				: 	redirect( config('fortify.login') . '?error=sso_mismatch' );
			}
		}

		$userProps = [];

		// get user data from driver
		if($driver=='google')			{ $this->setUserFromGoogle($ssoUser, $userProps); }
		elseif($driver=='facebook')		{ $this->setUserFromFacebook($ssoUser, $userProps); }
		elseif($driver=='apple')		{ $this->setUserFromApple($ssoUser, $userProps); }

		// create new user if not in database
		if(!$user) {
			try {
				$user = (new RegisterNewUser())->create([
					'email' => $userProps['email'],
					'name' => $userProps['name'],
					'surname' => $userProps['surname'],
					'lang' => config('app.fallback_locale'),
					'username' => strtolower($userProps['username']),
					'gender' => 'u',
					'terms' => true,
					'password' => substr($ssoUser->token,0,16),
					'password_confirmation' => substr($ssoUser->token,0,16),
				]);
			}
			catch(\Illuminate\Validation\ValidationException) {
				return $this->responseSsoError('validation_error');
			}

			$user->markEmailAsVerified();
			$user->changeRole('guest','user');
		}

		// update user data to always match sso data
		if($driver != 'apple') {
			$user->name 		= $userProps['name'];
			$user->surname 		= $userProps['surname'];
			$user->image 		= strlen($userProps['image']) < 256 ? $userProps['image'] : $user->image;	 	// prevent long google image urls
		}
		$user->sso_token 	= substr($ssoUser->token,0,150);
		$user->sso_driver 	= $driver;
		$user->save();

		Auth::login($user);

		if($this->nativeApp) {

			$deviceName = $request->header('device', 'Mobile App ('.$driver.')');

			// update token
			$token = $user->tokens()->where('name', $deviceName)->delete();
			$token = $user->createToken($deviceName)->plainTextToken;

			return $this->responseData($token);
		}
		else {
			return redirect(config('fortify.home'));
		}
	}


	protected function responseSsoError(string $message) {

		if($this->nativeApp) {
			return $this->responseError(403, $message);
		}
		else {
			return redirect(config('fortify.login').'?error='.$message);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function ensureUniqueUsername(string $username): string {

		$counter = 0;
		$base = rtrim($username, '0..9');

		while(User::whereUsername($username)->first()) {

			$counter++;
			$username = $base.$counter;

			if($counter>10) {$username = $base . random_int(1000,9999); } // prevent endless loop
		}

		return strtolower($username);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
