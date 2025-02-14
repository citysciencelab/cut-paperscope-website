<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// laravel
	require_once __DIR__ . '/../../../vendor/autoload.php';
	use function Laravel\Prompts\info;
	use Illuminate\Support\Str;

	// creator
	require_once 'traits/CreateTarget.php';
	require_once 'traits/CreateAttributes.php';
	require_once 'traits/CreateRelations.php';
	require_once 'traits/CreateMigration.php';
	require_once 'traits/CreateModel.php';
	require_once 'traits/CreateModelTest.php';
	require_once 'traits/CreateFactory.php';
	require_once 'traits/CreateAppController.php';
	require_once 'traits/CreateAppControllerTest.php';
	require_once 'traits/CreateSaveRequest.php';
	require_once 'traits/CreateSaveRequestTest.php';
	require_once 'traits/CreateBackendController.php';
	require_once 'traits/CreateBackendControllerTest.php';
	require_once 'traits/CreateJob.php';
	require_once 'traits/CreateResource.php';
	require_once 'traits/CreateRoutes.php';
	require_once 'traits/CreateBackendNavi.php';
	require_once 'traits/CreateBackendList.php';
	require_once 'traits/CreateBackendEdit.php';
	require_once 'traits/CreateCypressTest.php';
	require_once 'traits/CreateVuePage.php';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class CreateLaravelModel {

	// traits
	use CreateTarget, CreateAttributes, CreateMigration, CreateRelations,
	CreateModel, CreateModelTest,
	CreateFactory,
	CreateAppController, CreateAppControllerTest,
	CreateSaveRequest, CreateSaveRequestTest, CreateBackendController, CreateBackendControllerTest,
	CreateJob, CreateResource,CreateRoutes,
	CreateBackendNavi, CreateBackendList, CreateBackendEdit,
	CreateCypressTest, CreateVuePage;


	protected $userInput = [
		'target' => null,
		'attributes' => [],
		'relations' => [],
	];


	protected $isMultiLang = false;
	protected $defaultLang = 'de';
	protected $testMode = false;


	public function __construct() {

		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../../../');
		$dotenv->load();
		$this->isMultiLang = $_ENV['FEATURE_MULTI_LANG'] == 'true';
		$this->defaultLang = $_ENV['APP_FALLBACK_LOCALE'];

		$this->createTarget();
		$this->createAttributes();
		$this->createRelations();
		$this->createMigration();
		$this->createModel();
		$this->createModelTest();
		$this->createFactory();
		$this->createAppController();
		$this->createAppControllerTest();
		$this->createSaveRequest();
		$this->createSaveRequestTest();
		$this->createBackendController();
		$this->createBackendControllerTest();
		$this->createJob();
		$this->createResource();
		$this->createRoutes();
		$this->createBackendNavi();
		$this->createBackendList();
		$this->createBackendEdit();
		$this->createCypressTest();
		$this->createVuePage();

		// complete output
		info("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -");
		info("Model created successfully");
		info("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n");
		$info = "You should do the following:\n";
		$info .= "Check the created files and adjust them if necessary\n";
		$info .= "run \"php artisan migrate\"\n";
		$info .= "run \"php artisan migrate:fresh --seed --env=testing\"\n";
		$info .= "run \"npm run routes\"";
		info($info);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	protected function fillTemplate(string $template, string $file, Callable $callback) {

		// read template
		$template = file_get_contents(__DIR__ . '/templates/' . $template);

		// replace template vars
		$template = Str::replace('{{ModelClass}}', $this->userInput['name'], $template);
		$template = Str::replace('{{uppercase}}', Str::upper($this->userInput['name']), $template);
		$template = Str::replace('{{slug}}', $this->userInput['slug'], $template);
		$template = Str::replace('{{plural}}', $this->userInput['plural'], $template);
		$template = Str::replace('{{ModelClassPlural}}', Str::plural($this->userInput['name']), $template);

		// fill template
		$template = $callback($template);

		$projectPath = $this->testMode ? __DIR__ . '/tests/' : __DIR__ . '/../../../';
		if(!$this->testMode) {
			// create directory if not exists
			$path = $projectPath . pathinfo($file, PATHINFO_DIRNAME);
			if(!is_dir($path)) { mkdir($path, 0755, true); }
		}

		// save file
		if($this->testMode) { $file = basename($file); }
		info('Creating file: ' . $file);
		file_put_contents($projectPath.$file, $template);
	}


	protected function replaceLine(string &$file, string $search, string $replace) {

		if(($replace) == '') {
			$file = preg_replace('/^.*'.$search.'.*(\R)/m', $replace, $file);
		}
		else {
			$file = preg_replace('/^(.*)'.$search.'.*/m', '$1'.$replace, $file);
		}
	}


	protected function replace(string &$file, string $search, string $replace) {

		$file = str_replace($search, $replace, $file);
	}


	protected function commentSection(string $label) {

		return "/*".str_repeat("/", 167)."\n//\n//\t".Str::upper($label)."\n//\n".str_repeat("/", 166)." */\n\n\n";
	}


	protected function calculateTabs(string $label, string $longest) {

		if($label == $longest) { return "\t"; }

		$tabSize = 4;
		$tabs = ceil((strlen($longest) - strlen($label)) / $tabSize);
		return str_repeat("\t", $tabs);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


} // end class
