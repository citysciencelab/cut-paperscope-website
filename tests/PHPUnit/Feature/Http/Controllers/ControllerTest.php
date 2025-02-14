<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Feature\Http\Controllers;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Config;

	// App
	use App\Http\Controllers\Controller;
	use App\Models\App\Base\Item;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ControllerTest extends TestCase {

	use RefreshDatabase;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CONFIG
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_config_data() {

		// arrange
		$controller = new Controller();

		// act
		$config = $controller->getConfig();

		// assert: app name
		$this->assertEquals(config('app.name'), $config['app_name']);

		// assert: language keys
		$this->assertArrayHasKey('active_locale', $config);
		$this->assertArrayHasKey('available_locales', $config);
		$this->assertArrayHasKey('fallback_locale', $config);
		$this->assertIsArray($config['available_locales']);

		// assert: path keys
		$this->assertArrayHasKey('base_url', $config);
		$this->assertArrayHasKey('base_path', $config);
		$this->assertStringContainsString($config['base_path'], $config['base_url']);

		// assert: cookie keys
		$this->assertArrayHasKey('cookie_enabled', $config);
		$this->assertArrayHasKey('cookie_name', $config);
		$this->assertArrayHasKey('cookie_categories', $config);
		$this->assertArrayHasKey('cookie_expires', $config);
		$this->assertArrayHasKey('cookie_domain', $config);
		$this->assertArrayHasKey('cookie_path', $config);
		$this->assertIsBool($config['cookie_enabled']);
		$this->assertIsArray($config['cookie_categories']);

		// assert: no user data
		$this->assertNull($config['app_user']);
	}


	public function test_config_user_data() {

		// arrange
		$user = $this->loginAsUser();
		$controller = new Controller();

		// act
		$config = $controller->getConfig();

		// assert: user data
		$this->assertEquals($user->id, $config['app_user']['id']);
	}


	public function test_config_on_index_view() {

		// act
		$response = $this->get('/');

		// assert config present as javascript
		$response->assertSee('window.config = {');

		// assert: config propertries
		$response->assertSee('"app_name": "'.config('app.name').'",', false);
	}


	public function test_config_on_backend_view() {

		$this->skipIfNoBackend();

		// act
		$response = $this->get('/backend');

		// assert: config present as javascript
		$response->assertSee('window.config = {');

		// assert: propertries
		$response->assertSee('"app_name": "'.config('app.name').'",', false);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RESPONSE LIST
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_response_list() {

		// arrange
		$this->seed();
		$items = Item::all();

		// act
		$controller = new Controller();
		$response = $controller->responseList($items);
		$responseData = $response->getData(true);

		// assert: response
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('success', $responseData['status']);

		// assert: data
		$this->assertEquals($items->count(), count($responseData['data']));
	}


	public function test_empty_response_list() {

		// arrange: empty list
		$items = Item::wherePublic(2)->get();

		// act
		$controller = new Controller();
		$response = $controller->responseList($items);
		$responseData = $response->getData(true);

		// assert: response
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('success', $responseData['status']);

		// assert: data
		$this->assertEquals(0, count($responseData['data']));
	}


	public function test_response_list_with_paginator() {

		// arrange
		$this->seed();

		// arrange: paginated items
		$items = Item::factory()->count(100)->create();
		$items = Item::paginate(20);

		// act
		$controller = new Controller();
		$controller->enablePaginator();
		$response = $controller->responseList($items);
		$responseData = $response->getData(true);

		// assert: paginator enabled in response
		$this->assertTrue($responseData['paginator']);

		// assert: required paginator keys
		$this->assertArrayHasKey('currentPage', $responseData);
		$this->assertArrayHasKey('nextPageUrl', $responseData);
		$this->assertArrayHasKey('prevPageUrl', $responseData);
		$this->assertArrayHasKey('pages', $responseData);

		// assert: is on first page (starts at 1)
		$this->assertEquals(1, $responseData['currentPage']);
		$this->assertNull($responseData['prevPageUrl']);
		$this->assertStringEndsWith('?page=2', $responseData['nextPageUrl']);
	}


	public function test_default_paginate_range() {

		// act
		$controller = new Controller();

		// assert
		$this->assertEquals(25, $controller->getPaginatorRange());
	}


	public function test_response_list_with_other_resource_class() {

		// arrange
		$this->seed();
		$items = Item::all();

		// act
		$controller = new Controller();
		$response = $controller->responseList($items, 'App\Http\Resources\Base\ItemResource');
		$responseData = $response->getData(true);

		// assert
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('success', $responseData['status']);
		$this->assertEquals($items->count(), count($responseData['data']));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    RESPONSE GET
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_response_get() {

		// arrange
		$this->seed();
		$item = Item::first();

		// act
		$controller = new Controller();
		$response = $controller->responseGet($item);
		$responseData = $response->getData(true);

		// assert: response
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('success', $responseData['status']);

		// assert: data
		$this->assertEquals($item->id, $responseData['data']['id']);
	}


	public function test_empty_response_get() {

		// arrange
		$this->seed();

		// act
		$controller = new Controller();
		$response = $controller->responseGet();
		$responseData = $response->getData(true);

		// assert: response
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertEquals('error', $responseData['status']);
		$this->assertFalse( isset($responseData['data']) );

		// assert: error message
		$this->assertEquals(trans('api.not_found'), $responseData['message']);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    SLUG
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/** @dataProvider provideSlugData */
	public function test_slug($input, $inputDefault, $expectedSlug) {

		// act
		$controller = new Controller();
		$slug = $controller->createSlug($input, $inputDefault);

		// assert
		$this->assertEquals($expectedSlug, $slug);
	}


	static public function provideSlugData() {

		return [
			'excepted input' => [
				'Make this ä slug', '', 'make-this-ae-slug'
			],
			'empty input with default' => [
				'', 'default !', 'default'
			],
		];
	}


	public function test_empty_slug() {

		// arrange/assert
		$this->expectException(\Exception::class);

		// act
		$controller = new Controller();
		$controller->createSlug('', '');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LOGOUT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	public function test_logout_driver_file() {

		// arrange: create file session for user
		Config::set('session.driver', 'file');
		/** @var \App\Models\Auth\User $user **/
		$user = $this->loginAsUser();
		$this->withSession(['foo' => 'bar'])->get('/');

		// assert: user is logged in
		$this->assertTrue(Auth::check());

		// act
		$controller = new Controller();
		$controller->logoutFromAllDevices($user);

		// assert: user is logged out
		$this->assertFalse(Auth::check());
	}


	public function test_logout_driver_redis() {

		// arrange: create redis session for user
		Config::set('session.driver', 'redis');
		/** @var \App\Models\Auth\User $user **/
		$user = $this->loginAsUser();

		// arrange: force existing session file
		try {
			$this->withSession(['foo' => 'bar'])->get('/');
		} catch (\Exception $e) {
			$this->markTestSkipped('Redis not available');
			return;
		}

		// assert: check user is logged in
		$this->assertTrue(Auth::check());

		// act
		try {
			$controller = new Controller();
			$controller->logoutFromAllDevices($user);
		} catch (\Exception $e) {
			$this->assertTrue(true);
		}

		// assert: user is logged out
		$this->assertFalse(Auth::check());
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class

