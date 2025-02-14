<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Middleware;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Http\Request;
	use Illuminate\Http\Response;
	use Illuminate\Support\Facades\Auth;

	// App
	use App\Http\Middleware\AuthenticateSession;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class AuthenticateSessionTest extends TestCase {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TEST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_authenticate_via_remember() {

		// arrange
		$user = $this->loginAsUser();

		// arrange: request
		$request = Request::create('http://localhost/api/user', 'GET');
		$request->cookies->set('remember_web_123','123|456|'.$user->getAuthPassword());
		$request->setLaravelSession($this->app['session.store']);
		$request->setUserResolver(function () use ($user) { return $user; });

		// arrange: mock auth driver
		$authFactory = $this->mockAuthDriver($user);

		// act
		/** @var \Illuminate\Contracts\Auth\Factory $authFactory **/
		$middlware = new AuthenticateSession($authFactory);
		$next = $middlware->handle($request, function ($request) { return new Response(); });

		// assert
		$this->assertInstanceOf(Response::class, $next);
		$this->assertEquals(200, $next->getStatusCode());
	}


	public function test_authenticate_with_wrong_cookie() {

		// arrange
		$user = $this->loginAsUser();

		// arrange: request
		$request = Request::create('http://localhost/api/user', 'GET');
		$request->cookies->set('remember_web_123','123|456|wrong_password_hash');
		$request->setLaravelSession($this->app['session.store']);
		$request->setUserResolver(function () use ($user) { return $user; });

		// arrange: mock auth driver
		$authFactory = $this->mockAuthDriver($user);
		$this->expectException(\Illuminate\Auth\AuthenticationException::class);

		// act
		/** @var \Illuminate\Contracts\Auth\Factory $authFactory **/
		$middlware = new AuthenticateSession($authFactory);
		$next = $middlware->handle($request, function ($request) { return new Response(); });

		// assert
		$this->assertNull(Auth::user());
	}


	public function test_authenticate_with_wrong_session() {

		// arrange
		$user = $this->loginAsUser();

		// arrange: request
		$request = Request::create('http://localhost/api/user', 'GET');
		$request->cookies->set('remember_web_123','123|456|'.$user->getAuthPassword());
		$request->setUserResolver(function () use ($user) { return $user; });

		// arrange: prepare session
		$request->setLaravelSession($this->app['session.store']);
		$request->session()->put('password_hash_sanctum', 'wrong_password_hash');

		// arrange: mock auth driver
		$authFactory = $this->mockAuthDriver($user);
		$this->expectException(\Illuminate\Auth\AuthenticationException::class);

		// act
		/** @var \Illuminate\Contracts\Auth\Factory $authFactory **/
		$middlware = new AuthenticateSession($authFactory);
		$middlware->handle($request, function ($request) { return new Response(); });

		// assert
		$this->assertNull(Auth::user());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MOCKS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function mockAuthDriver(&$user) {

		$authFactory = $this->mock('Illuminate\Contracts\Auth\Factory');
		$authFactory->shouldReceive(('viaRemember'))->andReturn(true);
		$authFactory->shouldReceive('getRecallerName')->andReturn('remember_web_123');
		$authFactory->shouldReceive('user')->andReturn($user);
		$authFactory->shouldReceive('logoutCurrentDevice')->andReturn($user);

		return $authFactory;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class

