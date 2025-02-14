<?php

/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace Tests\PHPUnit\Unit\Helper;

	// Laravel
	use Tests\PHPUnit\TestCase;
	use Illuminate\Support\Facades\Route;

	// App
	use App\Helper\ApiRoutes;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class HelperApiRoutesTest extends TestCase {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    FRONTEND ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/** @dataProvider provideFrontendData */
	public function test_model_frontend_routes($modelName, $useId, $controllerName, $expectedControllerName, $expectedRouteName) {

		// arrange
		ApiRoutes::setModelAppRoutes($modelName, $useId, $controllerName);
		$routeCollection = Route::getRoutes();
		$routeCollection->refreshNameLookups();

		// assert: route names
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName.'.list'));
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName));

		// assert: list action
		$this->assertEquals('App\\Http\\Controllers\\App\\'.$expectedControllerName.'Controller@getPublicList', $routeCollection->getByName($expectedRouteName.'.list')->getActionName());

		// assert: get action
		$handler = $useId ? 'Controller@getPublic' : 'Controller@getPublicBySlug';
		$this->assertEquals('App\\Http\\Controllers\\App\\'.$expectedControllerName.$handler, $routeCollection->getByName($expectedRouteName)->getActionName());
	}


	static public function provideFrontendData() {

		return [
			"expected input" => [
				'Adam', true, null,
				'Adam', 'api.adam',
			],
			"model with two parts" => [
				'abo-exercise', false, null,
				'AboExercise', 'api.abo-exercise'
			],
			"lowercase input" => [
				'adam', true, null,
				'Adam', 'api.adam'
			],
			"uppercase input" => [
				'ADAM',	false, null,
				'Adam', 'api.adam'
			],
			"different controller name" => [
				'Adam', true, 'Mike',
				'Mike', 'api.adam'
			],
		];
	}




/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/** @dataProvider provideBackendData */
	public function test_model_backend_routes($modelName, $controllerName, $isChildRoute, $expectedControllerName, $expectedRouteName) {

		// arrange
		ApiRoutes::setModelBackendRoutes($modelName, $isChildRoute, $controllerName);
		$routeCollection = Route::getRoutes();
		$routeCollection->refreshNameLookups();

		// assert: route names
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName.'.list'));
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName));
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName.'.save'));
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName.'.delete'));
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName.'.sort'));
		$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName.'.search'));

		// assert: route actions
		$this->assertEquals('App\\Http\\Controllers\\Backend\\'.$expectedControllerName.'Controller@getBackendList', $routeCollection->getByName($expectedRouteName.'.list')->getActionName());
		$this->assertEquals('App\\Http\\Controllers\\Backend\\'.$expectedControllerName.'Controller@getBackend', $routeCollection->getByName($expectedRouteName)->getActionName());
		$this->assertEquals('App\\Http\\Controllers\\Backend\\'.$expectedControllerName.'Controller@save', $routeCollection->getByName($expectedRouteName.'.save')->getActionName());
		$this->assertEquals('App\\Http\\Controllers\\Backend\\'.$expectedControllerName.'Controller@deleteModel', $routeCollection->getByName($expectedRouteName.'.delete')->getActionName());
		$this->assertEquals('App\\Http\\Controllers\\Backend\\'.$expectedControllerName.'Controller@sortModel', $routeCollection->getByName($expectedRouteName.'.sort')->getActionName());
		$this->assertEquals('App\\Http\\Controllers\\Backend\\'.$expectedControllerName.'Controller@search', $routeCollection->getByName($expectedRouteName.'.search')->getActionName());

		// correct child routes
		if($isChildRoute) {
			$this->assertTrue($routeCollection->hasNamedRoute($expectedRouteName.'.list.child'));
			$this->assertEquals('App\\Http\\Controllers\\Backend\\'.$expectedControllerName.'Controller@getBackendChildListSorted', $routeCollection->getByName($expectedRouteName.'.search')->getActionName());
		}
	}


	static public function provideBackendData() {

		return [
			"expected input" => [
				'Adam',	null, false,
				'Adam', 'api.backend.adam'
			],
			"model with two parts" => [
				'abo-exercise',	null, false,
				'AboExercise', 'api.backend.abo-exercise'
			],
			"lowercase input" => [
				'adam',	null, false,
				'Adam', 'api.backend.adam'
			],
			"uppercase input" => [
				'ADAM',	null, false,
				'Adam', 'api.backend.adam'
			],
			"different controller name" => [
				'Adam',	'Mike', false,
				'Mike', 'api.backend.adam'
			],
		];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

} // end class
