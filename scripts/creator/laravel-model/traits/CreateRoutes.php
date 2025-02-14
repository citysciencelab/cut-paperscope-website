<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// laravel
	require_once __DIR__ . '/../../../../vendor/autoload.php';
	use Illuminate\Support\Str;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


trait CreateRoutes {



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createRoutes() {

		$template = file_get_contents(__DIR__ . '/../../../../routes/api/api_app.php');
		$this->replaceLine($template, '\/\/ \[add model app routes\]', $this->createAppRoutes());
		if($this->testMode) {
			file_put_contents(__DIR__ . '/../tests/api_app.php', $template);
		}
		else {
			file_put_contents(__DIR__ . '/../../../../routes/api/api_app.php', $template);
		}

		$template = file_get_contents(__DIR__ . '/../../../../routes/api/api_backend.php');
		$this->replaceLine($template, '\/\/ \[add model backend routes\]', $this->createBackendRoutes());
		if($this->testMode) {
			file_put_contents(__DIR__ . '/../tests/api_backend.php', $template);
		}
		else {
			file_put_contents(__DIR__ . '/../../../../routes/api/api_backend.php', $template);
		}

		$this->createJavascriptAppRoutes();
		$this->createJavascriptBackendRoutes();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    APP ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createAppRoutes() {

		$routes = "ApiRoutes::setModelAppRoutes('".$this->userInput['slug']."');\n\t";
		$routes .= '// [add model app routes]';

		return $routes;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    BACKEND ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createBackendRoutes() {

		$routes = "ApiRoutes::setModelBackendRoutes('".$this->userInput['slug']."'".($this->userInput['target']=='fragment'?',true':'').");\n\t\t";
		$routes .= '// [add model app routes]';

		return $routes;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function createJavascriptAppRoutes() {

		if($this->userInput['target'] != 'page') { return; }

		$modelClass = $this->userInput['name'];
		$slug = $this->userInput['slug'];

		// app include
		$router = file_get_contents(__DIR__ . '/../../../../resources/js/app/AppRouter.js');
		$includes = "// ".$slug."\n\t";
		$includes .= "const Page".$modelClass." = () => import('./pages/".$slug."/Page".$modelClass.".vue');";
		$includes .= "\n\n\t// [add model includes]";
		$this->replaceLine($router, '\/\/ \[add model includes\]', $includes);

		// app route
		$routes = "// ".$slug."\n\t\t";
		$routes .= "{ path: '".$slug."/:slug', name: '".$slug."', component: Page".$modelClass."},";
		$routes .= "\n\n\t\t// [add model routes]";
		$this->replaceLine($router, '\/\/ \[add model routes\]', $routes);

		if($this->testMode) {
			file_put_contents(__DIR__ . '/../tests/AppRouter.js', $router);
		}
		else {
			file_put_contents(__DIR__ . '/../../../../resources/js/app/AppRouter.js', $router);
		}
	}


	protected function createJavascriptBackendRoutes() {

		$modelClass = $this->userInput['name'];
		$slug = $this->userInput['slug'];

		// Backend includes
		$router = file_get_contents(__DIR__ . '/../../../../resources/js/backend/BackendRouter.js');
		$includes = "// ".$slug."\n\t";
		$includes .= "const Page".$modelClass."List = () => import('./pages/".$slug."/Page".$modelClass."List.vue');";
		$includes .= "\n\t";
		$includes .= "const Page".$modelClass."Edit = () => import('./pages/".$slug."/Page".$modelClass."Edit.vue');";
		$includes .= "\n\n\t// [add model includes]";
		$this->replaceLine($router, '\/\/ \[add model includes\]', $includes);

		// Backend routes
		$routes = "// ".$slug."\n\t\t";
		$routes .= "{ path: 'backend/".$slug."/', name: 'backend.".$slug."', component: Page".$modelClass."List, meta:{auth:true} },";
		$routes .= "\n\t\t";
		$routes .= "{ path: 'backend/".$slug."/edit/:id?', name:'backend.".$slug.".edit', component: Page".$modelClass."Edit, meta: {auth:true} },";
		$routes .= "\n\n\t\t// [add model routes]";
		$this->replaceLine($router, '\/\/ \[add model routes\]', $routes);

		if($this->testMode) {
			file_put_contents(__DIR__ . '/../tests/BackendRouter.js', $router);
		}
		else {
			file_put_contents(__DIR__ . '/../../../../resources/js/backend/BackendRouter.js', $router);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end trait
