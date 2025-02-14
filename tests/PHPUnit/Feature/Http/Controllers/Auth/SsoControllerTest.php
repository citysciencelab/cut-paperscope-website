<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers\Auth;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use App\Http\Controllers\Auth\SsoController;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Laravel\Socialite\Facades\Socialite;
	use Mockery;

	// App Models
	use App\Models\Auth\User;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class SsoControllerTest extends TestCase {

	use RefreshDatabase;


	protected function setUp(): void {

		parent::setUp();
		$this->skipIfNoAppAccounts();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GOOGLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_sso_with_google() {

		// act
		$response = $this->get('sso/google');
		$redirectUrl = $response->headers->get('Location');

		// assert: google redirect
		$response->assertStatus(302);
		$this->assertStringContainsString('https://accounts.google.com', $redirectUrl);
	}


	public function test_sso_callback_google_without_data() {

		// act
		$response = $this->get('sso/google/callback');

		// assert
		$response->assertStatus(302);
		$response->assertRedirect('/login?error=code_missing');
	}


	public function test_sso_callback_google() {

		$this->seed();

		// arrange
		$user = $this->mockGoogleCallback();

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($user));
		$redirectUrl = $response->headers->get('Location');

		// assert: redirect home
		$response->assertStatus(302);
		$this->assertEquals(config('fortify.home'), $redirectUrl);

		// assert: user exists
		$newUser = User::whereEmail($user['email'])->first();
		$this->assertNotNull($newUser);
	}


	public function test_sso_callback_google_with_long_image_url() {

		$this->seed();

		// arrange
		$imageUrl = 'https://lh3.googleusercontent.com/a-/ALV-UjWL7x27yWrY2rbrH3yvEr1rctQfkK-ZqPZ6aH1cybjpZLGHrxcGm5CL1MHiXYnV3_57sXODWbZK2SlhBcfnmjTdsIXZnavZJ2xA-tRBJprtZDIk7IXBL_MLrYYeDaEg5-DOvdVinhxDdNfTvBtnQrQm5MjCn6vY6T0RkSL9T4c_cZKWTdfhHi4Mx6sCiiiYbLMbn4Z5chrnszh-XZmiRju04EUv2eEMBwTATtj93dxeB7JBAQdmmsU6bP0vTP1HhlwvDUsEc2ZiXlDCFcSKyw-04PKHan0qmEFb_XEgmb31DD7UL_7JA5zwEDVXfxEvEsiVBiCiFr2ThM9hChunsMWAIfuuigb0oGBOWOyOR4a8T5i01oi2YsU-EzYY4HsfkKUM3t8Psner07exV7VzZ33pmshMqoV_onABRdT4ZwUDu-PD4niVesEOFpwGfUm0GExEFfLAiaD2TdqVhKfunA0GA92BcB5BRPENnczWykA4r_tE0rbZgVgsufo-JfC0MORSPdZsy5aV_A--eo6uzS6t8E7ME_nOggcwWOLYUwc-gGZopNleZv1A2psrvhgdy1cuoCg0NfQ9SJZ-MQGbdfxXF74pjwDKHndaYjIq_XIYYxggueVy1rEm7YxF9KWbezW4l6OIgQSGUx8fDivwuk7Q0HRuXu3NZdNra7VqDL9wJ-gk3PcQPfMq8fi-wpyIBW6m2lSPPB9kiyppuk0pX6W9KNpYZV7l-qiZDUjywSpirlY_Gnmr_-yrYj9iE0RsuBdipcUW9faeqCIh5hJtAtIWZp7BBsRc9JO4atdta8hxrxZTCEl53uX0VurEvCbunmOa8YtY-UBMOz9mluOIAyCAegIlFIm_1Js7xnLbT8wHpEKLVlRaZCG4XwtCQPX6p7WD3UaUY_tKvbwCPp7Z6yHeUo5vLlXjfmA9dwoYvsZB-CY=s96-c';
		$user = $this->mockGoogleCallback(['picture' => $imageUrl]);

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($user));
		$redirectUrl = $response->headers->get('Location');

		// assert: redirect home
		$response->assertStatus(302);
		$this->assertEquals(config('fortify.home'), $redirectUrl);

		// assert: user exists
		$newUser = User::whereEmail($user['email'])->first();
		$this->assertNotNull($newUser);
		$this->assertStringContainsString('storage/users', $newUser->image);
	}


	protected function mockGoogleCallback(array $userProps = []) {

		$user = [
			'email' => 'user@google.com',
			'given_name' => 'Max',
			'family_name' => 'Google',
			'picture' => 'this-is-a-picture-url',
			'code' => '123456789123456789123456789',
		];

		$user = array_merge($user, $userProps);

		/** @var \Mockery\LegacyMockInterface $googleUser  */
		$googleUser = Mockery::mock('Laravel\Socialite\Two\User');
		$googleUser->shouldReceive('getEmail')->andReturn($user['email']);
		$googleUser->token = '123456789123456789123456789';
		$googleUser->user = $user;

		/** @var \Mockery\LegacyMockInterface $provider  */
		$provider = Mockery::mock('Laravel\Socialite\Two\AbstractProvider');
		$provider->shouldReceive('userFromToken')->andReturn($googleUser);
		$provider->shouldReceive('user')->andReturn($googleUser);
		$provider->shouldReceive('stateless')->andReturn($provider);

		Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

		return $user;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FACEBOOK
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_sso_with_facebook() {

		// act
		$response = $this->get('sso/facebook');
		$redirectUrl = $response->headers->get('Location');

		// assert: facebook redirect
		$response->assertStatus(302);
		$this->assertStringContainsString('https://www.facebook.com', $redirectUrl);
	}


	public function test_sso_callback_facebook_without_data() {

		// act
		$response = $this->get('sso/facebook/callback');

		// assert
		$response->assertStatus(302);
		$response->assertRedirect('/login?error=code_missing');
	}


	public function test_sso_callback_facebook() {

		$this->seed();

		// arrange
		$user = $this->mockFacebookCallback();

		// act
		$response = $this->get('sso/facebook/callback?'.http_build_query($user));
		$redirectUrl = $response->headers->get('Location');

		// assert: redirect home
		$response->assertStatus(302);
		$this->assertEquals(config('fortify.home'), $redirectUrl);

		// assert: user exists
		$newUser = User::whereEmail($user['email'])->first();
		$this->assertNotNull($newUser);
	}


	protected function mockFacebookCallback(array $userProps = []) {

		$user = [
			'email' => 'user@facebook.com',
			'name' => 'Max Facebook',
			'avatar' => 'this-is-a-picture-url',
			'code' => '123456789123456789123456789',
		];

		$user = array_merge($user, $userProps);

		/** @var \Mockery\LegacyMockInterface $facebookUser */
		$facebookUser = Mockery::mock('Laravel\Socialite\Two\User');
		$facebookUser->shouldReceive('getEmail')->andReturn($user['email']);
		$facebookUser->token = '123456789123456789123456789';
		$facebookUser->user = $user;

		/** @var \Mockery\LegacyMockInterface $provider */
		$provider = Mockery::mock('Laravel\Socialite\Two\AbstractProvider');
		$provider->shouldReceive('user')->andReturn($facebookUser);
		$provider->shouldReceive('stateless')->andReturn($provider);

		Socialite::shouldReceive('driver')->with('facebook')->andReturn($provider);

		return $user;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	APPLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_sso_with_apple() {

		// act
		$response = $this->get('sso/apple');
		$redirectUrl = $response->headers->get('Location');

		// assert: apple redirect
		$response->assertStatus(302);
		$this->assertStringContainsString('https://appleid.apple.com', $redirectUrl);
	}


	public function test_sso_callback_apple_without_data() {

		// act
		$response = $this->post('sso/apple/callback');

		// assert
		$response->assertStatus(302);
		$response->assertRedirect('/login?error=code_missing');
	}


	public function test_sso_callback_apple() {

		$this->seed();

		// arrange
		$user = $this->mockAppleCallback();

		// act
		$response = $this->post('sso/apple/callback?'.http_build_query($user));
		$redirectUrl = $response->headers->get('Location');

		// assert: redirect home
		$response->assertStatus(302);
		$this->assertEquals(config('fortify.home'), $redirectUrl);

		// assert: user exists
		$newUser = User::whereEmail($user['email'])->first();
		$this->assertNotNull($newUser);
	}


	protected function mockAppleCallback(array $userProps = []) {

		$user = [
			'email' => 'user@facebook.com',
			'name' => ['firstName' => 'Max', 'lastName' => 'Apple'],
			'avatar' => 'this-is-a-picture-url',
			'code' => '123456789123456789123456789',
		];

		$user = array_merge($user, $userProps);

		/** @var \Mockery\LegacyMockInterface $appleUser */
		$appleUser = Mockery::mock('Laravel\Socialite\Two\User');
		$appleUser->shouldReceive('getEmail')->andReturn($user['email']);
		$appleUser->token = '123456789123456789123456789';
		$appleUser->user = $user;

		/** @var \Mockery\LegacyMockInterface $provider */
		$provider = Mockery::mock('Laravel\Socialite\Two\AbstractProvider');
		$provider->shouldReceive('user')->andReturn($appleUser);
		$provider->shouldReceive('stateless')->andReturn($provider);

		Socialite::shouldReceive('driver')->with('apple')->andReturn($provider);

		return $user;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	NATIVE APP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_sso_callback_from_app() {

		$this->seed();

		// arrange
		$user = $this->mockGoogleCallback();

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($user),[
			'device' => 'Testing Device',
			'X-Native-App' => 'true',
		]);
		$response->assertStatus(200);

		// assert: user exists
		$newUser = User::with('tokens')->whereEmail($user['email'])->first();
		$this->assertNotNull($newUser);

		// assert: valid token
		$responseToken = $response->json('data');
		$response = $this->get('/api/user', ['Authorization' => 'Bearer '.$responseToken]);
		$response->assertStatus(200);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EXCEPTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_callback_exception() {

		$this->seed();
		$this->mockGoogleException();

		// arrange
		$user = ['code' => '123456789123456789123456789'];

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($user));

		// assert
		$response->assertRedirect('/login?error=unknown_driver_error');
	}


	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function test_callback_exception_from_app() {

		$this->seed();
		$this->mockGoogleException();

		// arrange
		$user = ['code' => '123456789123456789123456789'];

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($user),[
			'device' => 'Testing Device',
			'X-Native-App' => 'true',
		]);

		// assert
		$response->assertStatus(403);
	}


	public function test_create_user_exception() {

		$this->seed();

		// arrange
		$user = $this->mockGoogleCallback(['email' => null]);

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($user));

		// assert
		$response->assertRedirect('/login?error=validation_error');
	}


	protected function mockGoogleException() {

		/** @var \Mockery\LegacyMockInterface $provider */
		$provider = Mockery::mock('overload:Laravel\Socialite\Two\GoogleProvider');
		$provider->shouldReceive('userFromToken')->andThrow(new \Exception('Unknown driver error'));
		$provider->shouldReceive('user')->andThrow(new \Exception('Unknown driver error'));
		$provider->shouldReceive('stateless')->andReturn($provider);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DRIVER MISMATCH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_db_user_with_google_callback() {

		$this->seed();

		// arrange
		$dbUser = User::first();
		$googleUser = $this->mockGoogleCallback(['email' => $dbUser->email]);

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($googleUser));

		// assert: redirect
		$response->assertStatus(302);

		// assert: redirect target is home route
		$redirectUrl = $response->headers->get('Location');
		$this->assertEquals(config('fortify.login') . '?error=sso_mismatch', $redirectUrl);
	}


	public function test_db_user_with_google_callback_from_app() {

		$this->seed();

		// arrange
		$dbUser = User::first();
		$googleUser = $this->mockGoogleCallback(['email' => $dbUser->email]);

		// act
		$response = $this->get('sso/google/callback?'.http_build_query($googleUser),[
			'device' => 'Testing Device',
			'X-Native-App' => 'true',
		]);

		// assert: api error
		$response->assertStatus(403);
		$errorMessage = $response->json('message');
		$this->assertEquals($errorMessage,'sso_mismatch');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/** @dataProvider provideUsernameData */
	public function test_method_ensureUniqueUsername(string $usernameInput, string $usernameExpected) {

		// act
		$this->createUser(['username'=>'existing.user10']);

		// assert
        $result = (new SsoController())->ensureUniqueUsername($usernameInput);

		// assert: new username
		$this->assertEquals($result, $usernameExpected);
    }


	static public function provideUsernameData() {

		return [
			"new username" => [ 'adam', 'adam' ],
			"capital username" => [ 'Adam', 'adam' ],
			"existing username" => [ 'existing.user10', 'existing.user1' ],
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
